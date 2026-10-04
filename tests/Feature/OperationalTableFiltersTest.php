<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Lead;
use App\Models\Media;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class OperationalTableFiltersTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_article_column_filters_combine_and_match_the_displayed_seo_state(): void
    {
        $admin = User::factory()->admin()->create();
        $author = User::factory()->editor()->create();
        $category = Category::factory()->create();
        $data = [
            'title' => 'Khám phá VF 7', 'user_id' => $author->id, 'category_id' => $category->id,
            'published_at' => '2026-10-04 23:59:59', 'seo_title' => 'VF 7', 'seo_description' => 'Giới thiệu VF 7',
            'media_id' => Media::factory()->create()->id,
        ];
        $matching = Post::factory()->published()->create($data);
        Post::factory()->published()->create(array_replace($data, ['category_id' => null]));
        Post::factory()->published()->create(array_replace($data, ['seo_description' => '']));
        Post::factory()->published()->create(array_replace($data, ['published_at' => '2026-10-05 00:00:00']));
        $response = $this->actingAs($admin)->get(route('admin.posts.index', [
            'q' => 'VF 7', 'status' => 'published', 'category_id' => $category->id, 'user_id' => $author->id,
            'seo' => 'complete', 'published_from' => '2026-10-04', 'published_to' => '2026-10-04',
        ]))->assertOk();

        $this->assertSame([$matching->id], $response->viewData('posts')->modelKeys());
        $this->get(route('admin.posts.index', ['published_to' => '2026-10-04']))
            ->assertOk()->assertViewHas('posts', fn (LengthAwarePaginator $records): bool => $records->total() === 3);
        $this->get(route('admin.posts.index', ['q' => '0']))
            ->assertOk()->assertViewHas('posts', fn (LengthAwarePaginator $records): bool => $records->total() === 0);
    }

    public function test_incomplete_seo_filter_keeps_editor_authorization_and_page_size(): void
    {
        $editor = User::factory()->editor()->create();
        $otherAuthor = User::factory()->editor()->create();
        Post::factory()->count(12)->create(['user_id' => $editor->id, 'seo_title' => 'VF 7']);
        Post::factory()->create(['user_id' => $otherAuthor->id, 'seo_title' => null]);
        $response = $this->actingAs($editor)->get(route('admin.posts.index', [
            'seo' => 'incomplete', 'per_page' => 10, 'page' => 2,
        ]))->assertOk();
        $posts = $response->viewData('posts');

        $this->assertSame(12, $posts->total());
        $this->assertCount(2, $posts);
        $this->assertSame(10, $posts->perPage());
        $this->assertStringContainsString('seo=incomplete', $posts->url(1));
        $this->assertStringContainsString('per_page=10', $posts->url(1));
        $this->assertTrue($posts->every(fn (Post $post): bool => $post->user_id === $editor->id));
        $this->get(route('admin.posts.index', ['user_id' => $otherAuthor->id, 'seo' => 'incomplete']))
            ->assertOk()->assertViewHas('posts', fn (LengthAwarePaginator $records): bool => $records->total() === 0);
    }

    public function test_lead_filters_use_the_displayed_time_and_keep_sales_scope(): void
    {
        $sales = User::factory()->sales()->create();
        $otherSales = User::factory()->sales()->create();
        $vehicle = Vehicle::factory()->create();
        $data = [
            'name' => 'Khách VF 7', 'type' => 'test_drive', 'status' => 'confirmed',
            'vehicle_id' => $vehicle->id, 'assigned_to' => $sales->id,
        ];
        $scheduled = Lead::factory()->create($data + [
            'appointment_at' => '2026-10-04 23:59:59', 'preferred_at' => '2026-10-05 09:00:00',
        ]);
        $preferred = Lead::factory()->create($data + ['preferred_at' => '2026-10-04 09:00:00']);
        $created = Lead::factory()->create($data + ['created_at' => '2026-10-04 00:00:00']);
        Lead::factory()->create(array_replace($data, ['assigned_to' => $otherSales->id]) + [
            'appointment_at' => '2026-10-04 09:00:00',
        ]);
        Lead::factory()->create($data + [
            'appointment_at' => '2026-10-05 00:00:00', 'preferred_at' => '2026-10-04 09:00:00',
        ]);
        $response = $this->actingAs($sales)->get(route('admin.leads.index', [
            'view' => 'list', 'q' => 'VF 7', 'type' => 'test_drive', 'status' => 'confirmed',
            'vehicle_id' => $vehicle->id, 'assigned_to' => $sales->id,
            'date_from' => '2026-10-04', 'date_to' => '2026-10-04', 'per_page' => 10,
        ]))->assertOk()->assertViewIs('admin.leads.index');

        $this->assertEqualsCanonicalizing([$scheduled->id, $preferred->id, $created->id],
            $response->viewData('leads')->modelKeys());
        $this->get(route('admin.leads.index', ['assigned_to' => $otherSales->id]))
            ->assertOk()->assertViewHas('leads', fn (LengthAwarePaginator $records): bool => $records->total() === 0);
        $this->get(route('admin.leads.index', ['assigned_to' => 'unassigned']))
            ->assertOk()->assertViewHas('leads', fn (LengthAwarePaginator $records): bool => $records->total() === 0);
    }

    public function test_manager_can_filter_unassigned_leads_and_use_a_single_date_bound(): void
    {
        $manager = User::factory()->manager()->create();
        $unassigned = Lead::factory()->create(['created_at' => '2026-10-04 23:59:59']);
        Lead::factory()->create(['assigned_to' => $manager->id, 'created_at' => '2026-10-04 09:00:00']);
        Lead::factory()->create(['created_at' => '2026-10-05 00:00:00']);
        $response = $this->actingAs($manager)->get(route('admin.leads.index', [
            'assigned_to' => 'unassigned', 'date_to' => '2026-10-04',
        ]))->assertOk();

        $this->assertSame([$unassigned->id], $response->viewData('leads')->modelKeys());
    }

    public function test_account_filters_include_locked_accounts_and_preserve_pagination(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->sales()->inactive()->count(12)->sequence(
            fn (Sequence $sequence): array => [
                'name' => 'Nhân viên '.$sequence->index, 'email' => 'staff'.$sequence->index.'@example.com',
            ],
        )->create();
        User::factory()->sales()->create([
            'name' => 'Nhân viên đang hoạt động', 'email' => 'active@example.com',
        ]);
        User::factory()->editor()->inactive()->create([
            'name' => 'Nhân viên biên tập', 'email' => 'edit@example.com',
        ]);
        $response = $this->actingAs($admin)->get(route('admin.users.index', [
            'name' => 'Nhân viên', 'email' => '@example.com', 'role' => 'sales',
            'is_active' => '0', 'per_page' => 10, 'page' => 2,
        ]))->assertOk();
        $users = $response->viewData('users');

        $this->assertSame(12, $users->total());
        $this->assertCount(2, $users);
        $this->assertStringContainsString('is_active=0', $users->url(1));
        $this->actingAs(User::factory()->manager()->create())->get(route('admin.users.index'))
            ->assertForbidden();
    }

    /** @return array<string, array{string, class-string<Category|Tag>}> */
    public static function taxonomyKinds(): array
    {
        return ['categories' => ['categories', Category::class], 'tags' => ['tags', Tag::class]];
    }

    /** @param class-string<Category|Tag> $modelClass */
    #[DataProvider('taxonomyKinds')]
    public function test_taxonomy_filters_combine_before_paginating(string $kind, string $modelClass): void
    {
        $editor = User::factory()->editor()->create();
        $modelClass::factory()->count(12)->sequence(
            fn (Sequence $sequence): array => [
                'name' => 'Mẫu xe '.$sequence->index, 'slug' => 'vf-'.$sequence->index,
            ],
        )->create();
        $modelClass::factory()->create(['name' => 'Mẫu xe khác', 'slug' => 'other']);
        $modelClass::factory()->create(['name' => 'Nội dung khác', 'slug' => 'vf-other']);
        $response = $this->actingAs($editor)->get(route('admin.taxonomies.index', [
            'kind' => $kind, 'name' => 'Mẫu xe', 'slug' => 'vf-', 'per_page' => 10, 'page' => 2,
        ]))->assertOk();
        $records = $response->viewData('records');

        $this->assertSame(12, $records->total());
        $this->assertCount(2, $records);
        $this->assertStringContainsString('slug=vf-', $records->url(1));
        $this->assertStringContainsString('per_page=10', $records->url(1));
    }

    /** @return array<string, array{string, array<string, string>}> */
    public static function operationalLists(): array
    {
        return [
            'posts' => ['admin.posts.index', []], 'leads' => ['admin.leads.index', []],
            'users' => ['admin.users.index', []], 'categories' => ['admin.taxonomies.index', ['kind' => 'categories']],
            'tags' => ['admin.taxonomies.index', ['kind' => 'tags']],
        ];
    }

    /** @param array<string, string> $parameters */
    #[DataProvider('operationalLists')]
    public function test_unsupported_page_sizes_are_rejected(string $routeName, array $parameters): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->getJson(route($routeName, $parameters + ['per_page' => 1000]))
            ->assertUnprocessable()->assertJsonValidationErrors('per_page');
    }
}
