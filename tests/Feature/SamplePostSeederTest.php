<?php

namespace Tests\Feature;

use App\Actions\Posts\AssessPostSeo;
use App\Actions\Posts\RenderPostContent;
use App\Enums\PostStatus;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Media;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use App\Models\Vehicle;
use Database\Seeders\SamplePostSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class SamplePostSeederTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_seeds_twenty_substantial_public_articles_with_metadata_sources_and_relations(): void
    {
        $this->freezeTime();
        $author = User::factory()->admin()->create();
        foreach (['vf5', 'vf6', 'vf7', 'vf8', 'vf9'] as $slug) {
            $cover = Media::factory()->create(['alt' => 'Ngoại thất VinFast '.strtoupper($slug)]);
            Vehicle::factory()->create(['slug' => $slug, 'media_id' => $cover->id]);
        }

        $this->seed(SamplePostSeeder::class);

        $this->assertDatabaseCount('posts', 20);
        $this->assertSame(20, Post::published()->count());
        $this->assertDatabaseCount('users', 1);
        $bodyHashes = [];
        foreach ($this->samples() as $sample) {
            $post = Post::query()->with(['category', 'tags', 'vehicles', 'media'])->where('slug', $sample['slug'])
                ->firstOrFail();
            $this->assertSame($author->id, $post->user_id);
            $this->assertSame(PostStatus::Published, $post->status);
            $this->assertNotNull($post->published_at);
            $this->assertTrue($post->published_at->lessThan(now()));
            $this->assertGreaterThanOrEqual(20, mb_strlen($post->seo_title));
            $this->assertLessThanOrEqual(65, mb_strlen($post->seo_title));
            $this->assertGreaterThanOrEqual(100, mb_strlen($post->seo_description));
            $this->assertLessThanOrEqual(160, mb_strlen($post->seo_description));
            $this->assertNotEmpty($post->excerpt);
            $this->assertNotEmpty($post->focus_keyword);
            $this->assertNull($post->canonical_url);
            $this->assertSame($sample['category'], $post->category->slug);
            $this->assertEqualsCanonicalizing($sample['vehicles'], $post->vehicles->pluck('slug')->all());
            $this->assertEqualsCanonicalizing($sample['tags'], $post->tags->pluck('name')->all());
            $this->assertNotNull($post->media);
            $this->assertNotEmpty($post->media->alt);
            $this->assertSame($post->media_id, $post->share_media_id);
            $this->assertStringNotContainsString('{{', $post->body);
            $this->assertMatchesRegularExpression('/\]\(\/bai-viet\//', $post->body);
            preg_match_all('/\]\(\/bai-viet\/([a-z0-9-]+)\)/', $post->body, $links);
            foreach ($links[1] as $linkedSlug) {
                $this->assertTrue(Post::query()->where('slug', $linkedSlug)->exists(), $linkedSlug);
            }
            $this->assertMatchesRegularExpression('/\]\(https:\/\/vinfastauto\.com\//', $post->body);
            $this->assertMatchesRegularExpression('/\]\(\/lien-he\?type=/', $post->body);
            $content = app(RenderPostContent::class)->handle($post->body);
            $wordCount = count(preg_split('/\s+/u', trim(strip_tags($content['html']))));
            $this->assertGreaterThanOrEqual(900, $wordCount, $post->slug);
            $this->assertLessThanOrEqual(1300, $wordCount, $post->slug);
            $this->assertGreaterThanOrEqual(8, count($content['headings']));
            $this->assertStringNotContainsString('<h1', $content['html']);
            $assessment = app(AssessPostSeo::class)->handle($post->toArray(), $content, $post->media);
            $this->assertGreaterThanOrEqual(85, $assessment['score'], $post->slug);
            $bodyHashes[] = hash('sha256', $post->body);
        }
        $this->assertCount(20, array_unique($bodyHashes));
        $first = Post::query()->firstOrFail();
        $this->get(route('posts.show', $first->slug))->assertOk()
            ->assertSee($first->title)->assertSee($first->seo_description);
        $this->get(route('posts.index'))->assertOk()
            ->assertViewHas('posts', fn ($posts): bool => $posts->total() === 20);
    }

    public function test_repeat_seed_preserves_edited_content_publication_and_existing_relations(): void
    {
        User::factory()->manager()->create();
        $this->seed(SamplePostSeeder::class);
        $post = Post::query()->where('slug', 'vf5-di-pho-hang-ngay')->firstOrFail();
        $tag = Tag::factory()->create(['name' => 'Thẻ do biên tập viên tạo']);
        $vehicle = Vehicle::factory()->create();
        $post->update(['title' => 'Tiêu đề được biên tập', 'body' => 'Nội dung riêng đã sửa.',
            'status' => PostStatus::Draft, 'published_at' => null, 'focus_keyword' => 'Chủ đề biên tập']);
        $post->tags()->sync([$tag->id]);
        $post->vehicles()->sync([$vehicle->id]);
        $snapshot = $post->fresh()->getAttributes();
        $categoryCount = Category::query()->count();
        $tagCount = Tag::query()->count();

        $this->seed(SamplePostSeeder::class);

        $this->assertDatabaseCount('posts', 20);
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('categories', $categoryCount);
        $this->assertDatabaseCount('tags', $tagCount);
        $this->assertSame($snapshot, $post->fresh()->getAttributes());
        $this->assertSame([$tag->id], $post->fresh()->tags->modelKeys());
        $this->assertSame([$vehicle->id], $post->fresh()->vehicles->modelKeys());
    }

    public function test_missing_admin_and_images_use_inactive_editor_without_privileged_credentials(): void
    {
        $this->seed(SamplePostSeeder::class);
        $author = User::query()->sole();

        $this->assertSame(UserRole::Editor, $author->role);
        $this->assertFalse($author->is_active);
        $this->assertSame('sample-editor@example.invalid', $author->email);
        $this->assertNotEmpty($author->password);
        $this->assertDatabaseCount('posts', 20);
        $this->assertSame(20, Post::query()->whereNull('media_id')->whereNull('share_media_id')->count());
        $passwordHash = $author->password;

        $this->seed(SamplePostSeeder::class);

        $this->assertDatabaseCount('users', 1);
        $this->assertSame($passwordHash, $author->fresh()->password);
        $this->assertDatabaseCount('posts', 20);
    }

    public function test_sample_content_is_not_seeded_in_production(): void
    {
        $this->app->instance('env', 'production');
        $this->artisan('db:seed', ['--class' => SamplePostSeeder::class, '--force' => true])->assertSuccessful();

        $this->assertDatabaseCount('posts', 0);
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('categories', 0);
        $this->assertDatabaseCount('tags', 0);
    }

    /** @return list<array<string, mixed>> */
    private function samples(): array
    {
        return json_decode(File::get(database_path('seeders/content/posts/index.json')),
            true, 512, JSON_THROW_ON_ERROR);
    }
}
