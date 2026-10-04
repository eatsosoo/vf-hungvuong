<?php

namespace Tests\Feature;

use App\Models\Media;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PostEditorTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_analysis_requires_an_active_content_role(): void
    {
        $url = route('admin.posts.analyze');
        $this->postJson($url, ['body' => '## Nội dung'])->assertUnauthorized();
        $this->actingAs(User::factory()->sales()->create())->postJson($url, ['body' => '## Nội dung'])
            ->assertForbidden();
        $this->actingAs(User::factory()->editor()->inactive()->create())->postJson($url, ['body' => '## Nội dung'])
            ->assertForbidden();
        foreach (['editor', 'manager', 'admin'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]))->postJson($url, ['body' => '## Nội dung'])
                ->assertOk()->assertJsonStructure(['html', 'headings', 'assessment' => ['score', 'checks']]);
        }
        $this->assertDatabaseCount('posts', 0);
    }

    public function test_preview_renders_markdown_tables_and_headings_and_removes_unsafe_html_and_urls(): void
    {
        $body = <<<'MARKDOWN'
        # Tư vấn VF 8

        Mở bài với **nội dung nổi bật** và [xe VF 8](/xe/vf-8).

        ## Thông số cần kiểm tra

        | Thông số | Giá trị |
        | --- | --- |
        | Số chỗ | 5 |

        <script>alert('unsafe')</script>

        <img src="x" onerror="alert('unsafe')">

        [Liên kết không an toàn](javascript:alert%281%29)

        ![Ảnh không an toàn](javascript:alert%281%29)

        - Danh sách **in đậm**
        - Nội dung `mã nguồn`
        MARKDOWN;
        $response = $this->actingAs(User::factory()->editor()->create())
            ->postJson(route('admin.posts.analyze'), ['body' => $body])->assertOk();
        $html = $response->json('html');
        $this->assertStringContainsString('<table>', $html);
        $this->assertStringContainsString('<strong>nội dung nổi bật</strong>', $html);
        $this->assertStringContainsString('<code>mã nguồn</code>', $html);
        $this->assertStringContainsString('<h2 id="section-1">Tư vấn VF 8</h2>', $html);
        $this->assertStringContainsString('<h2 id="section-2">Thông số cần kiểm tra</h2>', $html);
        foreach (['<h1', '<script', 'onerror=', 'javascript:'] as $unsafe) {
            $this->assertStringNotContainsString($unsafe, $html);
        }
        $response->assertJsonPath('headings.0.text', 'Tư vấn VF 8')
            ->assertJsonPath('headings.1.id', 'section-2');
        $this->assertDatabaseCount('posts', 0);
    }

    public function test_focus_keyword_is_saved_updated_and_can_be_cleared_on_own_draft(): void
    {
        $editor = User::factory()->editor()->create();
        $data = ['title' => 'Tư vấn xe điện VF 8', 'slug' => 'tu-van-vf-8', 'body' => '## Chọn xe',
            'status' => 'draft', 'focus_keyword' => 'xe điện VF 8'];
        $this->actingAs($editor)->post(route('admin.posts.store'), $data)->assertRedirect();
        $post = Post::query()->sole();
        $this->assertSame('xe điện VF 8', $post->focus_keyword);
        $this->assertSame($editor->id, $post->user_id);
        $this->put(route('admin.posts.update', $post), [...$data, 'focus_keyword' => 'lái thử VF 8'])
            ->assertSessionHas('success');
        $this->assertSame('lái thử VF 8', $post->fresh()->focus_keyword);
        $this->put(route('admin.posts.update', $post), [...$data, 'focus_keyword' => ''])
            ->assertSessionHas('success');
        $this->assertNull($post->fresh()->focus_keyword);
    }

    public function test_strikethrough_from_rich_editor_matches_preview_and_public_rendering(): void
    {
        $editor = User::factory()->editor()->create();
        $body = 'Nội dung ~~đã bỏ~~ và **nội dung mới**.';
        $response = $this->actingAs($editor)->postJson(route('admin.posts.analyze'), ['body' => $body])
            ->assertOk();
        $this->assertStringContainsString('<del>đã bỏ</del>', $response->json('html'));
        $post = Post::factory()->published()->create(['body' => $body]);
        $this->get(route('posts.show', $post->slug))->assertOk()->assertSee('<del>đã bỏ</del>', false)
            ->assertSee('<strong>nội dung mới</strong>', false);
    }

    public function test_focus_keyword_length_is_validated_for_preview_and_save(): void
    {
        $this->actingAs(User::factory()->editor()->create());
        $data = ['title' => 'Nội dung mẫu', 'slug' => 'noi-dung-mau', 'body' => 'Nội dung',
            'status' => 'draft', 'focus_keyword' => str_repeat('á', 121)];
        $this->postJson(route('admin.posts.analyze'), $data)
            ->assertUnprocessable()->assertJsonValidationErrors('focus_keyword');
        $this->post(route('admin.posts.store'), $data)->assertSessionHasErrors('focus_keyword');
        $this->assertDatabaseCount('posts', 0);
    }

    public function test_well_formed_content_has_a_complete_score_and_reading_guidance(): void
    {
        $cover = Media::factory()->create(['alt' => 'Mẫu xe VF 8 bên ngoài showroom']);
        $response = $this->actingAs(User::factory()->editor()->create())
            ->postJson(route('admin.posts.analyze'), $this->completeContent($cover))->assertOk();
        $assessment = $response->json('assessment');
        $this->assertSame(100, $assessment['score']);
        $this->assertSame('Sẵn sàng rà soát', $assessment['label']);
        $this->assertGreaterThanOrEqual(600, $assessment['wordCount']);
        $this->assertSame((int) ceil($assessment['wordCount'] / 200), $assessment['readingMinutes']);
        $this->assertSame(100, array_sum(array_column($assessment['checks'], 'max')));
        foreach ($assessment['checks'] as $check) {
            $this->assertTrue($check['passed'], $check['key']);
            $this->assertSame($check['max'], $check['points']);
            $this->assertNotSame('', $check['advice']);
        }
        $this->assertStringContainsString('Google không có số từ tối thiểu',
            $this->check($assessment, 'depth')['advice']);
    }

    public function test_empty_content_scores_zero_with_actionable_checks(): void
    {
        $response = $this->actingAs(User::factory()->editor()->create())
            ->postJson(route('admin.posts.analyze'), [])->assertOk();
        $assessment = $response->json('assessment');
        $this->assertSame(0, $assessment['score']);
        $this->assertSame(0, $assessment['wordCount']);
        $this->assertSame(1, $assessment['readingMinutes']);
        $this->assertSame('Cần bổ sung nội dung', $assessment['label']);
        foreach ($assessment['checks'] as $check) {
            $this->assertFalse($check['passed'], $check['key']);
            $this->assertSame(0, $check['points']);
            $this->assertNotSame('', $check['advice']);
        }
    }

    public function test_score_uses_seo_title_and_flags_missing_metadata_and_long_paragraphs(): void
    {
        $data = $this->completeContent();
        $data['seo_title'] = 'Ngắn';
        $data['seo_description'] = 'Quá ngắn';
        $data['slug'] = 'Slug Khó Đọc';
        $data['excerpt'] = '';
        $data['body'] = 'Mở đầu khác. '.str_repeat('nội dung ', 121)."\n\n".$data['body'];
        $response = $this->actingAs(User::factory()->editor()->create())
            ->postJson(route('admin.posts.analyze'), $data)->assertOk();
        $assessment = $response->json('assessment');
        $failedChecks = [
            'title', 'description', 'slug', 'keyword-title', 'keyword-intro', 'paragraphs', 'cover', 'excerpt',
        ];
        foreach ($failedChecks as $key) {
            $this->assertFalse($this->check($assessment, $key)['passed'], $key);
        }
        $this->assertTrue($this->check($assessment, 'depth')['passed']);
        $this->assertGreaterThanOrEqual(0, $assessment['score']);
        $this->assertLessThanOrEqual(100, $assessment['score']);
    }

    public function test_score_requires_alt_text_for_cover_and_every_inline_image(): void
    {
        $cover = Media::factory()->create(['alt' => '']);
        $data = $this->completeContent($cover);
        $data['body'] .= "\n\n![](/assets/other-car.webp)";
        $response = $this->actingAs(User::factory()->editor()->create())
            ->postJson(route('admin.posts.analyze'), $data)->assertOk();
        $assessment = $response->json('assessment');
        $this->assertFalse($this->check($assessment, 'cover')['passed']);
        $this->assertFalse($this->check($assessment, 'image-alt')['passed']);
        $this->assertSame(85, $assessment['score']);
    }

    public function test_score_distinguishes_same_host_internal_links_from_external_sources(): void
    {
        config(['app.url' => 'https://vinfast.example']);
        $data = ['body' => '[Xe tham khảo](https://vinfast.example/xe/vf-8)'];
        $this->actingAs(User::factory()->editor()->create());
        $assessment = $this->postJson(route('admin.posts.analyze'), $data)->assertOk()->json('assessment');
        $this->assertTrue($this->check($assessment, 'internal-links')['passed']);
        $this->assertFalse($this->check($assessment, 'sources')['passed']);
        $assessment = $this->postJson(route('admin.posts.analyze'), [
            'body' => '[Nguồn tham khảo](https://vinfastauto.com/vn_vi/)',
        ])->assertOk()->json('assessment');
        $this->assertFalse($this->check($assessment, 'internal-links')['passed']);
        $this->assertTrue($this->check($assessment, 'sources')['passed']);
    }

    public function test_preview_rejects_oversized_content_and_unknown_cover(): void
    {
        $this->actingAs(User::factory()->editor()->create());
        $this->postJson(route('admin.posts.analyze'), ['body' => str_repeat('a', 100001)])
            ->assertUnprocessable()->assertJsonValidationErrors('body');
        $this->postJson(route('admin.posts.analyze'), ['media_id' => 99999])
            ->assertUnprocessable()->assertJsonValidationErrors('media_id');
        $this->assertDatabaseCount('posts', 0);
    }

    public function test_editor_can_upload_json_image_and_get_optimized_webp_media(): void
    {
        Storage::fake('local');
        $editor = User::factory()->editor()->create();
        $response = $this->actingAs($editor)->postJson(route('admin.media.store'), [
            'image' => UploadedFile::fake()->image('vf-8.png', 2400, 1200),
            'alt' => 'VinFast VF 8 tại showroom',
        ])->assertCreated()->assertJsonPath('alt', 'VinFast VF 8 tại showroom')
            ->assertJsonPath('width', 1920)->assertJsonPath('height', 960);
        $media = Media::query()->sole();
        $this->assertSame($editor->id, $media->user_id);
        $this->assertSame($media->id, $response->json('id'));
        $this->assertSame($media->url(), $response->json('url'));
        $this->assertStringEndsWith('.webp', $media->path);
        Storage::disk('local')->assertExists($media->path);
        $bytes = Storage::disk('local')->get($media->path);
        $this->assertSame(IMAGETYPE_WEBP, getimagesizefromstring($bytes)[2]);
        $this->assertSame(strlen($bytes), $media->size);
    }

    public function test_json_upload_requires_content_role_and_rejects_unsafe_files(): void
    {
        Storage::fake('local');
        $url = route('admin.media.store');
        $this->actingAs(User::factory()->sales()->create())->postJson($url, [
            'image' => UploadedFile::fake()->image('car.jpg'),
        ])->assertForbidden();
        $this->actingAs(User::factory()->editor()->create())->postJson($url, [
            'image' => UploadedFile::fake()->createWithContent('unsafe.svg', '<svg><script>alert(1)</script></svg>'),
        ])->assertUnprocessable()->assertJsonValidationErrors('image');
        $this->postJson($url, ['image' => UploadedFile::fake()->image('car.jpg')->size(5121)])
            ->assertUnprocessable()->assertJsonValidationErrors('image');
        $this->postJson($url, [
            'image' => UploadedFile::fake()->image('car.jpg', 10, 10), 'alt' => str_repeat('a', 256),
        ])
            ->assertUnprocessable()->assertJsonValidationErrors('alt');
        $this->assertDatabaseCount('media', 0);
        $this->assertCount(0, Storage::disk('local')->allFiles());
    }

    public function test_analysis_endpoint_is_rate_limited(): void
    {
        $this->actingAs(User::factory()->editor()->create());
        for ($attempt = 0; $attempt < 90; $attempt++) {
            $this->postJson(route('admin.posts.analyze'), ['body' => 'Nội dung'])->assertOk();
        }
        $this->postJson(route('admin.posts.analyze'), ['body' => 'Nội dung'])
            ->assertTooManyRequests()->assertHeader('Retry-After');
    }

    /** @return array<string, mixed> */
    private function completeContent(?Media $cover = null): array
    {
        $paragraph = str_repeat('Đánh giá không gian và trải nghiệm vận hành thực tế. ', 8);

        return [
            'title' => 'Tư vấn xe điện VF 8 phù hợp với gia đình',
            'seo_title' => 'Xe điện VF 8: kinh nghiệm lựa chọn cho gia đình',
            'seo_description' => 'Tìm hiểu xe điện VF 8 theo nhu cầu gia đình, từ không gian, lịch lái thử '
                .'đến các bước kiểm tra thông số và chuẩn bị trước khi mua xe.',
            'focus_keyword' => 'xe điện VF 8',
            'slug' => 'xe-dien-vf-8-cho-gia-dinh',
            'excerpt' => 'Hướng dẫn chọn xe dựa trên nhu cầu sử dụng và trải nghiệm lái thử thực tế.',
            'media_id' => $cover?->id,
            'body' => 'Xe điện VF 8 cần được đánh giá theo nhu cầu của cả gia đình.'
                ."\n\n## Không gian và tiện ích\n\n".implode("\n\n", array_fill(0, 4, $paragraph))
                ."\n\n## Trải nghiệm thực tế\n\n".implode("\n\n", array_fill(0, 4, $paragraph))
                ."\n\n![VF 8 tại showroom](/assets/vf-8.webp)"
                ."\n\n[Xem mẫu xe](/xe/vf-8) và [Thông tin chính thức](https://vinfastauto.com/vn_vi/).",
        ];
    }

    /** @param array<string, mixed> $assessment
     * @return array<string, mixed>
     */
    private function check(array $assessment, string $key): array
    {
        return collect($assessment['checks'])->firstWhere('key', $key);
    }
}
