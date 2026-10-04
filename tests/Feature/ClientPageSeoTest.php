<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Media;
use App\Models\Page;
use App\Models\Post;
use App\Models\Promotion;
use App\Models\Tag;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleVariant;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class ClientPageSeoTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_post_metadata_preserves_editor_values_and_article_schema_matches_content(): void
    {
        $this->freezeTime();
        $author = User::factory()->create(['name' => 'Nguyễn An']);
        $category = Category::factory()->create(['name' => 'Kinh nghiệm xe điện']);
        $tag = Tag::factory()->create(['name' => 'Sạc pin']);
        $cover = Media::factory()->create(['alt' => 'Xe điện tại đại lý']);
        $shareImage = Media::factory()->create(['alt' => 'Ảnh chia sẻ bài viết']);
        $canonical = route('posts.show', 'huong-dan-sac-xe');
        $post = Post::factory()->published()->create([
            'user_id' => $author->id,
            'category_id' => $category->id,
            'title' => 'Hướng dẫn sạc xe điện',
            'seo_title' => 'Kinh nghiệm sạc xe điện tại nhà',
            'seo_description' => 'Những lưu ý khi sạc xe điện tại nhà.',
            'canonical_url' => $canonical,
            'media_id' => $cover->id,
            'share_media_id' => $shareImage->id,
            'body' => "## Bắt đầu\n\nKiểm tra nguồn điện trước khi sạc.",
        ]);
        $post->tags()->attach($tag);

        $response = $this->get(route('posts.show', $post->slug))->assertOk();
        $document = $this->document($response);
        $this->assertSame($post->seo_title, trim($document->query('//title')->item(0)->textContent));
        $this->assertSame($post->seo_description, $this->meta($document, 'name', 'description'));
        $this->assertSame($canonical, $document->query('//link[@rel="canonical"]')->item(0)->getAttribute('href'));
        $this->assertSame($shareImage->url(), $this->meta($document, 'property', 'og:image'));
        $this->assertSame($shareImage->alt, $this->meta($document, 'property', 'og:image:alt'));
        $article = $this->schemaNode($response, 'Article');
        $this->assertSame($post->title, $article['headline']);
        $this->assertSame($author->name, $article['author']['name']);
        $this->assertSame($post->published_at->toIso8601String(), $article['datePublished']);
        $this->assertSame($post->updated_at->toIso8601String(), $article['dateModified']);
        $this->assertSame($cover->url(), $article['image']);
        $this->assertSame($canonical, $article['mainEntityOfPage']['@id']);
        $this->assertSame($category->name, $article['articleSection']);
        $this->assertSame([$tag->name], $article['keywords']);
        $breadcrumb = $this->schemaNode($response, 'BreadcrumbList');
        $this->assertSame($post->title, $breadcrumb['itemListElement'][3]['name']);
        $this->assertSame(4, $breadcrumb['itemListElement'][3]['position']);
    }

    public function test_post_description_falls_back_to_readable_markdown_and_missing_images_are_omitted(): void
    {
        $post = Post::factory()->published()->create([
            'title' => 'Hướng dẫn sử dụng',
            'body' => "## Chuẩn bị\n\nKiểm tra **xe điện** trước khi đi.\n\n"
                .'[Đọc tiếp](https://example.com)',
        ]);

        $response = $this->get(route('posts.show', $post->slug))->assertOk();
        $document = $this->document($response);
        $this->assertSame('Chuẩn bị Kiểm tra xe điện trước khi đi. Đọc tiếp',
            $this->meta($document, 'name', 'description'));
        $this->assertSame(0, $document->query('//meta[@property="og:image"]')->count());
        $this->assertArrayNotHasKey('image', $this->schemaNode($response, 'Article'));
        $response->assertViewHas('readingMinutes', 1);
    }

    public function test_markdown_top_level_headings_keep_one_page_title_and_remain_in_table_of_contents(): void
    {
        $body = "# Chuẩn bị hành trình\n\nKiểm tra xe trước khi đi.";
        $post = Post::factory()->published()->create(['body' => $body]);
        $promotion = Promotion::factory()->create(['body' => $body]);
        $page = Page::factory()->create(['body' => $body]);

        foreach ([route('posts.show', $post->slug), route('promotions.show', $promotion->slug),
            route('pages.show', $page->slug)] as $url) {
            $response = $this->get($url)->assertOk();
            $document = $this->document($response);
            $this->assertSame(1, $document->query('//h1')->count());
            $heading = $document->query('//h2[@id="section-1"]')->item(0);
            $this->assertNotNull($heading);
            $this->assertSame('Chuẩn bị hành trình', $heading->textContent);
        }
        $this->get(route('posts.show', $post->slug))->assertOk()->assertViewHas('content',
            fn (array $content): bool => $content['headings'] === [
                ['id' => 'section-1', 'text' => 'Chuẩn bị hành trình'],
            ]);
    }

    public function test_listing_taxonomies_and_schema_only_include_published_due_posts(): void
    {
        $this->freezeTime();
        $publicCategory = Category::factory()->create(['name' => 'Danh mục công khai']);
        $privateCategory = Category::factory()->create(['name' => 'Danh mục chưa xuất bản']);
        $publicTag = Tag::factory()->create(['name' => 'Thẻ công khai']);
        $privateTag = Tag::factory()->create(['name' => 'Thẻ chưa xuất bản']);
        $published = Post::factory()->published()->create(['category_id' => $publicCategory->id]);
        $published->tags()->attach($publicTag);
        $draft = Post::factory()->create(['category_id' => $privateCategory->id]);
        $draft->tags()->attach($privateTag);
        $scheduled = Post::factory()->scheduled()->create(['category_id' => $privateCategory->id]);
        $future = Post::factory()->published()->create([
            'category_id' => $privateCategory->id,
            'published_at' => now()->addDay(),
        ]);

        $response = $this->get(route('posts.index'))->assertOk()->assertSee($published->title)
            ->assertDontSee($privateCategory->name)->assertDontSee($privateTag->name)
            ->assertDontSee($draft->title)->assertDontSee($scheduled->title)->assertDontSee($future->title);
        $items = $this->schemaNode($response, 'CollectionPage')['mainEntity']['itemListElement'];
        $this->assertSame([route('posts.show', $published->slug)], array_column($items, 'url'));
        $this->assertSame(0,
            $this->document($response)->query('//meta[@property="article:published_time"]')->count());
        $response->assertViewHas('categories', fn ($categories): bool => $categories->count() === 1
            && $categories->first()->posts_count === 1);
        $response->assertViewHas('tags', fn ($tags): bool => $tags->count() === 1
            && $tags->first()->posts_count === 1);
    }

    public function test_listing_canonical_retains_filters_and_page_without_tracking_parameters(): void
    {
        $category = Category::factory()->create();
        $tag = Tag::factory()->create();
        Post::factory()->published()->count(13)->create(['category_id' => $category->id])
            ->each(fn (Post $post) => $post->tags()->attach($tag));
        $filters = ['category' => $category->slug, 'tag' => $tag->slug, 'page' => 2];

        $response = $this->get(route('posts.index', $filters + ['utm_source' => 'test']))->assertOk();
        $document = $this->document($response);
        $this->assertSame(route('posts.index', $filters),
            $document->query('//link[@rel="canonical"]')->item(0)->getAttribute('href'));
        $this->assertSame('index,follow', $this->meta($document, 'name', 'robots'));
        $items = $this->schemaNode($response, 'CollectionPage')['mainEntity']['itemListElement'];
        $this->assertSame(13, $items[0]['position']);
        $firstPage = $this->get(route('posts.index', ['page' => 1]))->assertOk();
        $this->assertSame(route('posts.index'),
            $this->document($firstPage)->query('//link[@rel="canonical"]')->item(0)->getAttribute('href'));
    }

    public function test_search_and_empty_listings_are_noindex_with_followable_links(): void
    {
        Post::factory()->published()->create(['title' => 'Hướng dẫn sạc xe']);
        $search = $this->get(route('posts.index', ['q' => 'sạc']))->assertOk();
        $this->assertSame('noindex,follow', $this->meta($this->document($search), 'name', 'robots'));
        $empty = $this->get(route('posts.index', ['category' => 'khong-co-bai-viet']))->assertOk();
        $this->assertSame('noindex,follow', $this->meta($this->document($empty), 'name', 'robots'));
    }

    public function test_vehicle_schema_uses_real_content_without_reference_price_offers(): void
    {
        $media = Media::factory()->create(['alt' => 'Ngoại thất xe']);
        $vehicle = Vehicle::factory()->create([
            'name' => 'VinFast VF 7',
            'description' => 'Mẫu xe điện dành cho hành trình hằng ngày.',
            'media_id' => $media->id,
            'specifications' => ['Số chỗ' => 5],
        ]);
        VehicleVariant::factory()->create(['vehicle_id' => $vehicle->id]);
        $publishedPost = Post::factory()->published()->create();
        $publishedPost->vehicles()->attach($vehicle);
        $draftPost = Post::factory()->create();
        $draftPost->vehicles()->attach($vehicle);
        $hidden = Vehicle::factory()->create(['is_active' => false]);

        $response = $this->get(route('vehicles.show', $vehicle->slug))->assertOk()
            ->assertSee($publishedPost->title)->assertDontSee($draftPost->title);
        $document = $this->document($response);
        $this->assertSame(route('vehicles.show', $vehicle->slug),
            $document->query('//link[@rel="canonical"]')->item(0)->getAttribute('href'));
        $this->assertSame($vehicle->description, $this->meta($document, 'name', 'description'));
        $this->assertSame($media->url(), $this->meta($document, 'property', 'og:image'));
        $schema = $this->schemaNode($response, 'Vehicle');
        $this->assertSame($vehicle->name, $schema['name']);
        $this->assertSame($vehicle->description, $schema['description']);
        $this->assertArrayNotHasKey('offers', $schema);
        $this->assertArrayNotHasKey('aggregateRating', $schema);
        $this->assertSame(0, $document->query('//meta[@property="article:published_time"]')->count());
        $this->get(route('vehicles.show', $hidden->slug))->assertNotFound();
        $minimal = Vehicle::factory()->create();
        $minimalResponse = $this->get(route('vehicles.show', $minimal->slug))->assertOk();
        $this->assertStringContainsString($minimal->name,
            $this->meta($this->document($minimalResponse), 'name', 'description'));
        $this->assertArrayNotHasKey('description', $this->schemaNode($minimalResponse, 'Vehicle'));
        $this->assertArrayNotHasKey('image', $this->schemaNode($minimalResponse, 'Vehicle'));
    }

    public function test_preview_keeps_metadata_but_has_no_article_schema_or_public_access(): void
    {
        $editor = User::factory()->editor()->create();
        $post = Post::factory()->create(['user_id' => $editor->id]);
        $this->get(route('posts.show', $post->slug))->assertNotFound();
        $this->get(route('admin.posts.preview', $post))->assertRedirect(route('login'));

        $response = $this->actingAs($editor)->get(route('admin.posts.preview', $post))->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        $document = $this->document($response);
        $this->assertSame('noindex,nofollow', $this->meta($document, 'name', 'robots'));
        $this->assertSame(0, $document->query('//script[@type="application/ld+json"]')->count());
        $response->assertViewHas('readingMinutes', 1);
    }

    private function document(TestResponse $response): \DOMXPath
    {
        $document = new \DOMDocument;
        @$document->loadHTML($response->getContent());

        return new \DOMXPath($document);
    }

    private function meta(\DOMXPath $document, string $attribute, string $name): string
    {
        $node = $document->query('//meta[@'.$attribute.'="'.$name.'"]')->item(0);
        $this->assertNotNull($node, 'Missing meta '.$name);

        return $node->getAttribute('content');
    }

    /** @return array<string, mixed> */
    private function schemaNode(TestResponse $response, string $type): array
    {
        $nodes = $this->document($response)->query('//script[@type="application/ld+json"]');
        foreach ($nodes as $node) {
            $data = json_decode($node->textContent, true, 512, JSON_THROW_ON_ERROR);
            foreach ($data['@graph'] ?? [$data] as $item) {
                if (($item['@type'] ?? null) === $type) {
                    return $item;
                }
            }
        }
        $this->fail('Missing structured data node '.$type);
    }
}
