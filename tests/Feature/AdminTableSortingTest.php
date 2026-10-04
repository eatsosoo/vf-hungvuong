<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Lead;
use App\Models\Media;
use App\Models\Page;
use App\Models\Post;
use App\Models\Promotion;
use App\Models\Tag;
use App\Models\User;
use App\Models\Vehicle;
use DOMDocument;
use DOMXPath;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdminTableSortingTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * @param  class-string<Model>  $modelClass
     * @param  array<string, mixed>  $lowerAttributes
     * @param  array<string, mixed>  $higherAttributes
     * @param  array<string, mixed>  $parameters
     */
    #[DataProvider('sortableColumns')]
    public function test_columns_sort_both_directions_before_paginating(
        string $routeName,
        string $modelClass,
        string $viewKey,
        string $column,
        array $lowerAttributes,
        array $higherAttributes,
        array $parameters = [],
    ): void {
        $this->actingAs(User::factory()->admin()->create(['name' => 'Administrator']));
        $defaults = $modelClass === User::class ? ['name' => 'Sort target'] : [];
        $higher = $modelClass::factory()->create($higherAttributes + $defaults);
        $lower = $modelClass::factory()->create($lowerAttributes + $defaults);
        if ($modelClass === User::class) {
            $parameters['name'] = 'Sort target';
        }
        if ($modelClass === AuditLog::class) {
            $parameters['subject_type'] = Post::class;
        }

        foreach (['asc' => [$lower->id, $higher->id], 'desc' => [$higher->id, $lower->id]] as $direction => $ids) {
            $response = $this->get(route($routeName, $parameters + [
                'sort' => $column, 'direction' => $direction, 'per_page' => 10,
            ]))->assertOk();
            $records = $response->viewData($viewKey);
            $this->assertSame($ids, $records->pluck('id')->all());
            $this->assertSame(10, $records->perPage());
        }
    }

    /** @return array<string, array<int, mixed>> */
    public static function sortableColumns(): array
    {
        $cases = [];
        foreach ([
            'promotions' => [Promotion::class, 'title'],
            'pages' => [Page::class, 'title'],
            'vehicles' => [Vehicle::class, 'name'],
        ] as $resource => [$model, $name]) {
            foreach ([
                'name' => [[$name => 'Alpha'], [$name => 'Zulu']],
                'slug' => [['slug' => 'alpha'], ['slug' => 'zulu']],
                'is_active' => [['is_active' => false], ['is_active' => true]],
            ] as $column => [$lower, $higher]) {
                $cases[$resource.'-'.$column] = [
                    'admin.'.$resource.'.index', $model, 'records', $column, $lower, $higher,
                ];
            }
        }
        foreach (['categories' => Category::class, 'tags' => Tag::class] as $kind => $model) {
            foreach (['name', 'slug'] as $column) {
                $cases[$kind.'-'.$column] = [
                    'admin.taxonomies.index', $model, 'records', $column,
                    [$column => 'Alpha'], [$column => 'Zulu'], ['kind' => $kind],
                ];
            }
        }
        foreach ([
            'name' => [['name' => 'Sort target Alpha'], ['name' => 'Sort target Zulu']],
            'email' => [['email' => 'alpha@example.com'], ['email' => 'zulu@example.com']],
            'role' => [['role' => 'editor'], ['role' => 'sales']],
            'is_active' => [['is_active' => false], ['is_active' => true]],
        ] as $column => [$lower, $higher]) {
            $cases['users-'.$column] = ['admin.users.index', User::class, 'users', $column, $lower, $higher];
        }
        foreach ([
            'title' => [['title' => 'Alpha'], ['title' => 'Zulu']],
            'status' => [['status' => 'draft'], ['status' => 'scheduled']],
        ] as $column => [$lower, $higher]) {
            $cases['posts-'.$column] = ['admin.posts.index', Post::class, 'posts', $column, $lower, $higher];
        }
        foreach ([
            'name' => [['name' => 'Alpha'], ['name' => 'Zulu']],
            'type' => [['type' => 'consultation'], ['type' => 'quote']],
            'status' => [['status' => 'cancelled'], ['status' => 'new']],
        ] as $column => [$lower, $higher]) {
            $cases['leads-'.$column] = ['admin.leads.index', Lead::class, 'leads', $column, $lower, $higher];
        }
        foreach ([
            'created_at' => [['created_at' => '2026-10-01 12:00:00'], ['created_at' => '2026-10-03 12:00:00']],
            'action' => [['action' => 'created'], ['action' => 'updated']],
            'subject' => [['subject_id' => 2], ['subject_id' => 11]],
            'changed_fields' => [['changed_fields' => ['alpha']], ['changed_fields' => ['zulu']]],
        ] as $column => [$lower, $higher]) {
            $cases['audit-'.$column] = ['admin.audit.index', AuditLog::class, 'logs', $column, $lower, $higher];
        }

        foreach ([
            ['admin.promotions.index', Promotion::class, 'records', []],
            ['admin.pages.index', Page::class, 'records', []],
            ['admin.vehicles.index', Vehicle::class, 'records', []],
            ['admin.users.index', User::class, 'users', []],
            ['admin.posts.index', Post::class, 'posts', []],
            ['admin.leads.index', Lead::class, 'leads', []],
            ['admin.taxonomies.index', Category::class, 'records', ['kind' => 'categories']],
            ['admin.taxonomies.index', Tag::class, 'records', ['kind' => 'tags']],
            ['admin.audit.index', AuditLog::class, 'logs', []],
        ] as [$route, $model, $key, $parameters]) {
            foreach (['id', 'created_at', 'updated_at'] as $column) {
                $lower = $column === 'id' ? 9001 : '2026-10-01 12:00:00';
                $higher = $column === 'id' ? 9002 : '2026-10-03 12:00:00';
                $cases[$model.'-'.$column] = [
                    $route, $model, $key, $column, [$column => $lower], [$column => $higher], $parameters,
                ];
            }
        }

        return $cases;
    }

    public function test_relation_columns_sort_by_displayed_names_instead_of_foreign_ids(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $zulu = User::factory()->sales()->create(['name' => 'Zulu']);
        $alpha = User::factory()->sales()->create(['name' => 'Alpha']);
        $zuluVehicle = Vehicle::factory()->create(['name' => 'Zulu']);
        $alphaVehicle = Vehicle::factory()->create(['name' => 'Alpha']);
        $zuluPost = Post::factory()->create(['user_id' => $zulu->id]);
        $alphaPost = Post::factory()->create(['user_id' => $alpha->id]);
        $zuluLead = Lead::factory()->create(['assigned_to' => $zulu->id, 'vehicle_id' => $zuluVehicle->id]);
        $alphaLead = Lead::factory()->create(['assigned_to' => $alpha->id, 'vehicle_id' => $alphaVehicle->id]);
        $zuluLog = AuditLog::factory()->create(['user_id' => $zulu->id, 'action' => 'sort_fixture']);
        $alphaLog = AuditLog::factory()->create(['user_id' => $alpha->id, 'action' => 'sort_fixture']);

        foreach ([
            ['admin.posts.index', 'posts', 'author', $alphaPost->id, $zuluPost->id],
            ['admin.leads.index', 'leads', 'vehicle', $alphaLead->id, $zuluLead->id],
            ['admin.leads.index', 'leads', 'assignee', $alphaLead->id, $zuluLead->id],
            ['admin.audit.index', 'logs', 'actor', $alphaLog->id, $zuluLog->id],
        ] as [$route, $key, $column, $lowerId, $higherId]) {
            foreach (['asc' => [$lowerId, $higherId], 'desc' => [$higherId, $lowerId]] as $direction => $ids) {
                $parameters = ['sort' => $column, 'direction' => $direction];
                if ($key === 'logs') {
                    $parameters['action'] = 'sort_fixture';
                }
                $response = $this->get(route($route, $parameters))->assertOk();
                $this->assertSame($ids, $response->viewData($key)->pluck('id')->all());
            }
        }
        $this->actingAs($alpha)->get(route('admin.leads.index', ['sort' => 'vehicle']))
            ->assertOk()->assertViewHas('leads',
                fn (LengthAwarePaginator $records): bool => $records->modelKeys() === [$alphaLead->id]);
    }

    public function test_seo_sort_matches_the_completeness_badge_and_preserves_editor_scope(): void
    {
        $editor = User::factory()->editor()->create();
        $complete = Post::factory()->create([
            'user_id' => $editor->id, 'seo_title' => 'Title', 'seo_description' => 'Description',
            'media_id' => Media::factory()->create()->id,
        ]);
        $incomplete = Post::factory()->create(['user_id' => $editor->id, 'seo_title' => 'Title']);
        Post::factory()->create();
        $this->actingAs($editor);
        foreach ([
            'asc' => [$incomplete->id, $complete->id], 'desc' => [$complete->id, $incomplete->id],
        ] as $dir => $ids) {
            $response = $this->get(route('admin.posts.index', ['sort' => 'seo', 'direction' => $dir]))->assertOk();
            $this->assertSame($ids, $response->viewData('posts')->modelKeys());
        }
    }

    public function test_lead_time_sort_uses_appointment_then_preferred_then_created_time(): void
    {
        $this->actingAs(User::factory()->manager()->create());
        $appointment = Lead::factory()->create([
            'appointment_at' => '2026-10-06 09:00:00', 'preferred_at' => '2026-10-01 09:00:00',
        ]);
        $preferred = Lead::factory()->create(['preferred_at' => '2026-10-04 09:00:00']);
        $created = Lead::factory()->create(['created_at' => '2026-10-02 09:00:00']);
        foreach ([
            'asc' => [$created->id, $preferred->id, $appointment->id],
            'desc' => [$appointment->id, $preferred->id, $created->id],
        ] as $direction => $ids) {
            $response = $this->get(route('admin.leads.index', ['sort' => 'time', 'direction' => $direction]))
                ->assertOk();
            $this->assertSame($ids, $response->viewData('leads')->modelKeys());
        }
    }

    public function test_daily_report_sorts_aggregated_counts_and_dates(): void
    {
        $this->actingAs(User::factory()->manager()->create());
        Lead::factory()->count(3)->create(['created_at' => '2026-10-01 10:00:00']);
        Lead::factory()->create(['created_at' => '2026-10-02 10:00:00']);
        Lead::factory()->count(2)->create(['created_at' => '2026-10-03 10:00:00']);
        foreach ([
            ['total', 'asc', ['2026-10-02', '2026-10-03', '2026-10-01']],
            ['total', 'desc', ['2026-10-01', '2026-10-03', '2026-10-02']],
            ['day', 'asc', ['2026-10-01', '2026-10-02', '2026-10-03']],
            ['day', 'desc', ['2026-10-03', '2026-10-02', '2026-10-01']],
        ] as [$column, $direction, $days]) {
            $response = $this->get(route('admin.reports.index', ['sort' => $column, 'direction' => $direction]))
                ->assertOk()->assertViewHas('total', 6);
            $this->assertSame($days, $response->viewData('daily')->pluck('day')->all());
        }
    }

    public function test_sort_links_reset_page_keep_filters_and_expose_current_direction(): void
    {
        $this->freezeTime();
        $this->actingAs(User::factory()->admin()->create());
        $rows = Promotion::factory()->count(12)->create(['title' => 'Sort target']);
        $parameters = ['q' => 'Sort target', 'sort' => 'name', 'direction' => 'asc', 'per_page' => 10, 'page' => 2];
        $response = $this->get(route('admin.promotions.index', $parameters))->assertOk();
        $this->assertSame($rows->pluck('id')->reverse()->values()->slice(10)->values()->all(),
            $response->viewData('records')->modelKeys());
        $document = new DOMDocument;
        @$document->loadHTML('<?xml encoding="UTF-8">'.$response->getContent());
        $xpath = new DOMXPath($document);
        $this->assertCount(1, $xpath->query('//th[@aria-sort="ascending"]'));
        $this->assertCount(0, $xpath->query('//thead//*[@popovertarget]'));
        $link = $xpath->query('//th[@aria-sort="ascending"]/a')->item(0);
        parse_str(parse_url($link->getAttribute('href'), PHP_URL_QUERY), $query);
        $this->assertSame('desc', $query['direction']);
        $this->assertSame('name', $query['sort']);
        $this->assertSame('Sort target', $query['q']);
        $this->assertSame('10', $query['per_page']);
        $this->assertArrayNotHasKey('page', $query);
        $next = $xpath->query('//nav[@aria-label="Phân trang"]//a[@rel="prev"]')->item(0);
        parse_str(parse_url($next->getAttribute('href'), PHP_URL_QUERY), $previousQuery);
        $this->assertSame('name', $previousQuery['sort']);
        $this->assertSame('asc', $previousQuery['direction']);
        $this->assertCount(1, $xpath->query('//form[@id="table-filters"]/input[@name="sort"]'));
        $this->get($link->getAttribute('href'))->assertOk()->assertSee('aria-sort="descending"', false);
    }

    /** @return array<string, array{string, array<string, string>}> */
    public static function listRoutes(): array
    {
        return [
            'promotions' => ['admin.promotions.index', []], 'pages' => ['admin.pages.index', []],
            'vehicles' => ['admin.vehicles.index', []], 'posts' => ['admin.posts.index', []],
            'leads' => ['admin.leads.index', []], 'users' => ['admin.users.index', []],
            'categories' => ['admin.taxonomies.index', ['kind' => 'categories']],
            'tags' => ['admin.taxonomies.index', ['kind' => 'tags']],
            'audit' => ['admin.audit.index', []], 'reports' => ['admin.reports.index', []],
        ];
    }

    /** @param array<string, string> $parameters */
    #[DataProvider('listRoutes')]
    public function test_sorting_rejects_unlisted_columns_and_directions(string $route, array $parameters): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $this->getJson(route($route, $parameters + [
            'sort' => 'id desc; DROP TABLE users', 'direction' => 'sideways',
        ]))->assertUnprocessable()->assertJsonValidationErrors(['sort', 'direction']);
    }
}
