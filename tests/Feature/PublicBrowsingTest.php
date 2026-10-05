<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Media;
use App\Models\Post;
use App\Models\PostRedirect;
use App\Models\Promotion;
use App\Models\Tag;
use App\Models\Vehicle;
use App\Models\VehicleColor;
use DOMDocument;
use DOMXPath;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PublicBrowsingTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->freezeTime();
    }

    public function test_homepage_limits_public_content_and_prioritizes_newest_articles_and_promotions(): void
    {
        Vehicle::factory()->count(7)->create();
        $hiddenVehicle = Vehicle::factory()->create(['is_active' => false]);
        $olderPost = Post::factory()->published()->create(['published_at' => now()->subDays(2)]);
        $latestPosts = Post::factory()->published()->count(3)->create(['published_at' => now()->subHour()]);
        $draft = Post::factory()->create();
        $futurePost = Post::factory()->published()->create(['published_at' => now()->addDay()]);
        $olderPromotion = Promotion::factory()->create(['created_at' => now()->subDays(2)]);
        $latestPromotions = Promotion::factory()->count(3)->create(['created_at' => now()->subHour()]);
        $futurePromotion = Promotion::factory()->create([
            'starts_at' => now()->addDay(), 'ends_at' => now()->addDays(2),
        ]);

        $this->get(route('home'))->assertOk()
            ->assertViewHas('vehicles', fn (Collection $vehicles): bool => $vehicles->count() === 6
                && ! $vehicles->contains($hiddenVehicle))
            ->assertViewHas('posts', fn (Collection $posts): bool => $posts->count() === 3
                && $posts->diff($latestPosts)->isEmpty())
            ->assertViewHas('promotions', fn (Collection $promotions): bool => $promotions->count() === 3
                && $promotions->diff($latestPromotions)->isEmpty())
            ->assertDontSee($olderPost->title)->assertDontSee($draft->title)->assertDontSee($futurePost->title)
            ->assertDontSee($olderPromotion->title)->assertDontSee($futurePromotion->title);
    }

    public function test_promotion_dates_are_inclusive_across_detail_listing_vehicle_and_sitemap(): void
    {
        $vehicle = Vehicle::factory()->create();
        $promotion = Promotion::factory()->create(['starts_at' => now(), 'ends_at' => now()]);
        $promotion->vehicles()->attach($vehicle);

        $this->get(route('promotions.show', $promotion->slug))->assertOk()->assertSee($promotion->title);
        $this->get(route('promotions.index'))->assertOk()
            ->assertViewHas('promotions', fn (LengthAwarePaginator $items): bool => $items->total() === 1);
        $this->get(route('vehicles.show', $vehicle->slug))->assertOk()
            ->assertViewHas('promotions', fn (Collection $items): bool => $items->contains($promotion));
        $this->get(route('sitemap.page', ['section' => 'promotions', 'page' => 1]))
            ->assertOk()->assertSee(route('promotions.show', $promotion->slug), false);

        $this->travel(1)->seconds();
        $this->get(route('promotions.show', $promotion->slug))->assertNotFound();
        $this->get(route('promotions.index'))->assertOk()
            ->assertViewHas('promotions', fn (LengthAwarePaginator $items): bool => $items->total() === 0);
        $this->get(route('sitemap.page', ['section' => 'promotions', 'page' => 1]))->assertNotFound();
    }

    public function test_promotion_vehicle_filters_count_only_active_offers_and_preserve_pagination_and_language(): void
    {
        $vehicle = Vehicle::factory()->create();
        $otherVehicle = Vehicle::factory()->create();
        $hiddenVehicle = Vehicle::factory()->create(['is_active' => false]);
        $promotions = Promotion::factory()->count(13)->create();
        foreach ($promotions as $promotion) {
            $promotion->vehicles()->attach([$vehicle->id, $hiddenVehicle->id]);
        }
        $unrelated = Promotion::factory()->create();
        $unrelated->vehicles()->attach($otherVehicle);
        $expired = Promotion::factory()->create(['ends_at' => now()->subSecond()]);
        $expired->vehicles()->attach($vehicle);

        $query = ['vehicle' => $vehicle->slug];
        $response = $this->get(route('en.promotions.index', $query))->assertOk()
            ->assertViewHas('activeVehicle', fn (Vehicle $active): bool => $active->is($vehicle))
            ->assertViewHas('promotions', fn (LengthAwarePaginator $items): bool => $items->total() === 13
                && $items->count() === 12 && $items->first()->is($promotions->last()))
            ->assertViewHas('promotionVehicles', fn (Collection $vehicles): bool => $vehicles
                ->firstWhere('id', $vehicle->id)->promotions_count === 13 && ! $vehicles->contains($hiddenVehicle))
            ->assertDontSee($hiddenVehicle->name)->assertDontSee($unrelated->title)->assertDontSee($expired->title);
        $document = $this->document($response);
        $nextPage = $document->query('//a[@rel="next"]')->item(0)->getAttribute('href');
        $this->assertStringContainsString('/en/khuyen-mai?', $nextPage);
        $this->assertStringContainsString('vehicle='.$vehicle->slug, $nextPage);
        $this->get($nextPage)->assertOk()
            ->assertViewHas('promotions', fn (LengthAwarePaginator $items): bool => $items->count() === 1
                && $items->first()->is($promotions->first()));
        $this->get(route('promotions.index', ['vehicle' => $hiddenVehicle->slug]))->assertOk()
            ->assertViewHas('promotions', fn (LengthAwarePaginator $items): bool => $items->total() === 0);
        $this->get(route('promotions.show', $promotions->first()->slug))->assertOk()
            ->assertViewHas('vehicles', fn (Collection $vehicles): bool => $vehicles->modelKeys() === [$vehicle->id]);
    }

    public function test_vehicle_catalog_combines_search_and_segment_before_pagination(): void
    {
        $matching = Vehicle::factory()->count(13)->sequence(fn ($sequence): array => [
            'name' => sprintf('Xe điện gia đình %02d', $sequence->index), 'segment' => 'SUV',
        ])->create();
        Vehicle::factory()->create(['name' => 'Xe điện khác', 'segment' => 'SUV']);
        Vehicle::factory()->create(['name' => 'Xe điện gia đình khác', 'segment' => 'Sedan']);
        $hidden = Vehicle::factory()->create([
            'name' => 'Xe điện gia đình ẩn', 'segment' => 'Riêng', 'is_active' => false,
        ]);
        $query = ['q' => 'gia đình', 'segment' => 'SUV'];
        $response = $this->get(route('en.vehicles.index', $query))->assertOk()
            ->assertViewHas('vehicles', fn (LengthAwarePaginator $items): bool => $items->total() === 13
                && $items->count() === 12 && $items->first()->is($matching->first()))
            ->assertViewHas('segments', fn ($segments): bool => ! $segments->contains('Riêng'))
            ->assertDontSee($hidden->name);
        $nextPage = $this->document($response)->query('//a[@rel="next"]')->item(0)->getAttribute('href');
        $this->assertStringContainsString('/en/xe?', $nextPage);
        parse_str(parse_url($nextPage, PHP_URL_QUERY), $nextQuery);
        $this->assertSame($query + ['page' => '2'], $nextQuery);
        $this->get($nextPage)->assertOk()
            ->assertViewHas('vehicles', fn (LengthAwarePaginator $items): bool => $items->count() === 1
                && $items->first()->is($matching->last()));
    }

    public function test_article_search_combines_category_tag_and_keyword_and_excludes_unpublished_matches(): void
    {
        $category = Category::factory()->create();
        $otherCategory = Category::factory()->create();
        $tag = Tag::factory()->create();
        $match = Post::factory()->published()->create(['title' => 'Hướng dẫn pin', 'category_id' => $category->id]);
        $wrongCategory = Post::factory()->published()->create([
            'title' => 'Hướng dẫn pin khác', 'category_id' => $otherCategory->id,
        ]);
        $wrongKeyword = Post::factory()->published()->create(['title' => 'Lái thử', 'category_id' => $category->id]);
        $draft = Post::factory()->create(['title' => 'Pin nháp', 'category_id' => $category->id]);
        foreach ([$match, $wrongCategory, $wrongKeyword, $draft] as $post) {
            $post->tags()->attach($tag);
        }
        Post::factory()->published()->create(['title' => 'Pin không gắn thẻ', 'category_id' => $category->id]);

        $this->get(route('posts.index', ['q' => 'pin', 'category' => $category->slug, 'tag' => $tag->slug]))
            ->assertOk()->assertViewHas('posts', fn (LengthAwarePaginator $posts): bool => $posts->total() === 1
                && $posts->first()->is($match));
    }

    #[DataProvider('searchablePages')]
    public function test_literal_zero_is_a_valid_search_term(string $routeName, string $viewKey): void
    {
        if ($viewKey === 'vehicles') {
            $match = Vehicle::factory()->create(['name' => 'VinFast VF 0']);
            Vehicle::factory()->create(['name' => 'VinFast VF 7']);
        } else {
            $match = Post::factory()->published()->create(['title' => 'Pin 0']);
            Post::factory()->published()->create(['title' => 'Pin xe điện']);
        }

        $this->get(route($routeName, ['q' => '0']))->assertOk()
            ->assertViewHas($viewKey, fn (LengthAwarePaginator $items): bool => $items->total() === 1
                && $items->first()->is($match));
    }

    /** @return array<string, array{string, string}> */
    public static function searchablePages(): array
    {
        return [
            'vehicles vietnamese' => ['vehicles.index', 'vehicles'],
            'vehicles english' => ['en.vehicles.index', 'vehicles'],
            'articles vietnamese' => ['posts.index', 'posts'],
            'articles english' => ['en.posts.index', 'posts'],
        ];
    }

    public function test_vehicle_color_selection_ignores_missing_images_and_colors_from_another_vehicle(): void
    {
        $cover = Media::factory()->create();
        $vehicle = Vehicle::factory()->create(['media_id' => $cover->id]);
        $red = VehicleColor::factory()->create(['vehicle_id' => $vehicle->id, 'media_id' => Media::factory()]);
        $coverColor = VehicleColor::factory()->create(['vehicle_id' => $vehicle->id, 'media_id' => $cover->id]);
        $withoutImage = VehicleColor::factory()->create(['vehicle_id' => $vehicle->id]);
        $foreignColor = VehicleColor::factory()->create(['media_id' => Media::factory()]);

        $this->get(route('vehicles.show', $vehicle->slug))->assertOk()
            ->assertViewHas('selectedColor', fn (VehicleColor $color): bool => $color->is($coverColor))
            ->assertViewHas('availableColors', fn (Collection $colors): bool => $colors->count() === 2
                && $colors->contains($red) && $colors->contains($coverColor));
        foreach ([$withoutImage, $foreignColor] as $unavailable) {
            $this->get(route('vehicles.show', ['slug' => $vehicle->slug, 'color' => $unavailable->id]))->assertOk()
                ->assertViewHas('selectedColor', fn (VehicleColor $color): bool => $color->is($coverColor));
        }
        $query = ['slug' => $vehicle->slug, 'color' => $red->id];
        $response = $this->get(route('en.vehicles.show', $query))->assertOk()
            ->assertViewHas('selectedColor', fn (VehicleColor $color): bool => $color->is($red));
        $document = $this->document($response);
        $this->assertSame($red->media->url(),
            $document->query('//img[@data-vehicle-image]')->item(0)->getAttribute('src'));
        $this->assertSame(route('vehicles.show', $query),
            $document->query('//a[@hreflang="vi"]')->item(0)->getAttribute('href'));
        $this->assertSame(route('en.vehicles.show', $vehicle->slug),
            $document->query('//link[@rel="canonical"]')->item(0)->getAttribute('href'));
    }

    public function test_legacy_article_urls_redirect_to_the_latest_slug_in_the_current_language(): void
    {
        $post = Post::factory()->published()->create();
        PostRedirect::factory()->create(['slug' => 'bai-viet-cu', 'post_id' => $post->id]);

        $this->get(route('en.posts.show', 'bai-viet-cu'))->assertStatus(301)
            ->assertRedirect(route('en.posts.show', $post->slug));
    }

    public function test_public_structured_data_has_a_valid_schema_org_context(): void
    {
        $vehicle = Vehicle::factory()->create();
        $post = Post::factory()->published()->create();

        foreach ([route('vehicles.show', $vehicle->slug), route('posts.show', $post->slug),
            route('posts.index')] as $url) {
            $response = $this->get($url)->assertOk();
            $script = $this->document($response)->query('//script[@type="application/ld+json"]')->item(0);
            $this->assertNotNull($script);
            $data = json_decode($script->textContent, true, 512, JSON_THROW_ON_ERROR);
            $this->assertArrayHasKey('@context', $data);
            $this->assertSame('https://schema.org', $data['@context']);
            $this->assertNotEmpty($data['@graph']);
        }
    }

    /** @param array<string, mixed> $query */
    #[DataProvider('invalidQueries')]
    public function test_public_filters_reject_malformed_queries(string $routeName, array $query, string $field): void
    {
        $vehicle = Vehicle::factory()->create(['slug' => 'vf-query-test']);
        if ($routeName === 'vehicles.show') {
            $query['slug'] = $vehicle->slug;
        }
        $this->getJson(route($routeName, $query))->assertUnprocessable()->assertJsonValidationErrors($field);
    }

    /** @return array<string, array{string, array<string, mixed>, string}> */
    public static function invalidQueries(): array
    {
        return [
            'catalog search array' => ['vehicles.index', ['q' => ['vf']], 'q'],
            'catalog segment array' => ['vehicles.index', ['segment' => ['SUV']], 'segment'],
            'article long search' => ['posts.index', ['q' => str_repeat('a', 101)], 'q'],
            'article category array' => ['posts.index', ['category' => ['pin']], 'category'],
            'article tag array' => ['posts.index', ['tag' => ['pin']], 'tag'],
            'article zero page' => ['posts.index', ['page' => 0], 'page'],
            'offer malformed slug' => ['promotions.index', ['vehicle' => '../vf7'], 'vehicle'],
            'offer zero page' => ['promotions.index', ['page' => 0], 'page'],
            'color array' => ['vehicles.show', ['color' => [1]], 'color'],
            'color zero' => ['vehicles.show', ['color' => 0], 'color'],
            'color negative' => ['vehicles.show', ['color' => -1], 'color'],
            'color not integer' => ['vehicles.show', ['color' => 'blue'], 'color'],
        ];
    }

    private function document(TestResponse $response): DOMXPath
    {
        $document = new DOMDocument;
        @$document->loadHTML('<?xml encoding="UTF-8">'.$response->getContent());

        return new DOMXPath($document);
    }
}
