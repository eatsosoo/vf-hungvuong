<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Lead;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ReportTableFiltersTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_daily_chart_covers_the_full_date_range_independently_of_table_filters_and_pagination(): void
    {
        $this->actingAs(User::factory()->manager()->createQuietly());
        Lead::factory()->create(['created_at' => '2026-10-02 12:00:00']);
        Lead::factory()->count(3)->create(['created_at' => '2026-10-04 12:00:00']);
        Lead::factory()->create(['created_at' => '2026-09-30 12:00:00']);

        $response = $this->get(route('admin.reports.index', [
            'from' => '2026-10-01', 'to' => '2026-10-05', 'total_min' => '3',
            'sort' => 'total', 'direction' => 'desc', 'per_page' => '10', 'page' => '2',
        ]))->assertOk()->assertViewHas('total', 4)
            ->assertViewHas('chartDaily', function (Collection $rows): bool {
                return $rows->pluck('day')->all() === ['2026-10-02', '2026-10-04']
                    && $rows->pluck('total')->map(fn (mixed $total): int => (int) $total)->all() === [1, 3];
            });

        $document = new \DOMDocument;
        @$document->loadHTML($response->getContent());
        $xpath = new \DOMXPath($document);
        $chart = $xpath->query('//*[@data-daily-chart]')->item(0);
        $this->assertNotNull($chart);
        $this->assertSame([
            '2026-10-01' => 0, '2026-10-02' => 1, '2026-10-04' => 3, '2026-10-05' => 0,
        ], json_decode($chart->getAttribute('data-values'), true));
        $line = $xpath->query('//*[contains(@class, "daily-chart-line")]')->item(0);
        $points = array_map(fn (string $point): array => array_map('floatval', explode(',', $point)),
            explode(' ', $line->getAttribute('points')));
        $this->assertSame([480.0, 260.0], $points[2]);
    }

    public function test_audit_filters_every_column_and_combines_filters(): void
    {
        $admin = User::factory()->admin()->createQuietly();
        $manager = User::factory()->manager()->createQuietly();
        $this->actingAs($admin);
        $matching = AuditLog::factory()->create([
            'user_id' => $admin->id, 'action' => 'updated', 'subject_type' => Post::class, 'subject_id' => 7,
            'changed_fields' => ['title', 'status'], 'created_at' => '2026-10-02 12:00:00',
        ]);
        $other = AuditLog::factory()->create([
            'user_id' => $manager->id, 'action' => 'created', 'subject_type' => Lead::class, 'subject_id' => 7,
            'changed_fields' => ['phone'], 'created_at' => '2026-10-02 12:00:00',
        ]);
        $system = AuditLog::factory()->create([
            'user_id' => null, 'action' => 'updated', 'subject_type' => Post::class, 'subject_id' => 0,
            'changed_fields' => ['subtitle'], 'created_at' => '2026-10-02 12:00:00',
        ]);
        $older = AuditLog::factory()->create([
            'user_id' => $admin->id, 'action' => 'updated', 'subject_type' => Post::class, 'subject_id' => 7,
            'changed_fields' => ['title'], 'created_at' => '2026-10-01 12:00:00',
        ]);

        foreach ([
            [['from' => '2026-10-02', 'to' => '2026-10-02'], [$system->id, $other->id, $matching->id]],
            [['to' => '2026-10-01'], [$older->id]],
            [['user_id' => $admin->id], [$matching->id, $older->id]],
            [['user_id' => 'system'], [$system->id]],
            [['action' => 'updat'], [$system->id, $matching->id, $older->id]],
            [['subject_type' => Lead::class], [$other->id]],
            [['subject_id' => '0'], [$system->id]],
            [['changed_field' => 'title'], [$matching->id, $older->id]],
            [[
                'from' => '2026-10-02', 'to' => '2026-10-02', 'user_id' => $admin->id, 'action' => 'updated',
                'subject_type' => Post::class, 'subject_id' => '7', 'changed_field' => 'title',
            ], [$matching->id]],
        ] as [$filters, $expectedIds]) {
            $this->get(route('admin.audit.index', $filters))
                ->assertOk()
                ->assertViewHas(
                    'logs',
                    fn (LengthAwarePaginator $logs): bool => $logs->pluck('id')->all() === $expectedIds,
                );
        }
    }

    public function test_audit_pagination_preserves_filters_and_reset_restores_all_logs(): void
    {
        $admin = User::factory()->admin()->createQuietly();
        $this->actingAs($admin);
        $matching = AuditLog::factory()->count(23)->create([
            'user_id' => $admin->id, 'action' => 'updated', 'changed_fields' => ['title'],
            'created_at' => '2026-10-02 12:00:00',
        ]);
        AuditLog::factory()->count(2)->create(['action' => 'created', 'created_at' => '2026-10-02 12:00:00']);
        $filters = [
            'from' => '2026-10-02', 'to' => '2026-10-02', 'user_id' => (string) $admin->id,
            'action' => 'updated', 'changed_field' => 'title', 'per_page' => '10',
        ];
        $this->get(route('admin.audit.index', [...$filters, 'page' => '2']))
            ->assertOk()
            ->assertViewHas('logs', function (LengthAwarePaginator $logs) use ($matching, $filters): bool {
                $this->assertSame(23, $logs->total());
                $this->assertSame(10, $logs->perPage());
                $this->assertSame($matching->pluck('id')->reverse()->slice(10, 10)->values()->all(),
                    $logs->pluck('id')->all());
                parse_str(parse_url($logs->nextPageUrl(), PHP_URL_QUERY), $nextQuery);
                $this->assertSame([...$filters, 'page' => '3'], $nextQuery);

                return true;
            })
            ->assertSee('Xóa bộ lọc')
            ->assertSee('href="'.route('admin.audit.index').'"', false);
        $this->get(route('admin.audit.index'))->assertOk()
            ->assertViewHas('logs', function (LengthAwarePaginator $logs): bool {
                return $logs->total() === 25 && $logs->perPage() === 20;
            });
    }

    public function test_daily_count_filters_apply_to_groups_and_leave_date_statistics_unchanged(): void
    {
        $this->actingAs(User::factory()->manager()->createQuietly());
        Lead::factory()->create(['created_at' => '2026-10-01 12:00:00']);
        Lead::factory()->count(2)->create(['created_at' => '2026-10-02 12:00:00']);
        Lead::factory()->create(['created_at' => '2026-10-02 12:00:00', 'status' => 'won']);
        Lead::factory()->count(2)->create(['created_at' => '2026-10-03 12:00:00', 'status' => 'won']);
        Lead::factory()->count(4)->create(['created_at' => '2026-09-30 12:00:00']);
        $dates = ['from' => '2026-10-01', 'to' => '2026-10-03'];

        $this->get(route('admin.reports.index', [...$dates, 'total_min' => '2', 'total_max' => '2']))
            ->assertOk()
            ->assertViewHas('total', 6)
            ->assertViewHas('won', 3)
            ->assertViewHas('conversion', 50.0)
            ->assertViewHas('daily', function (LengthAwarePaginator $daily): bool {
                return $daily->total() === 1 && $daily->first()->day === '2026-10-03'
                    && (int) $daily->first()->total === 2;
            });
        $this->get(route('admin.reports.index', [...$dates, 'total_min' => '0', 'total_max' => '0']))
            ->assertOk()->assertViewHas('total', 6)
            ->assertViewHas('daily', fn (LengthAwarePaginator $daily): bool => $daily->total() === 0)
            ->assertSee('Chưa có dữ liệu trong khoảng lọc');
        $this->get(route('admin.reports.index', ['to' => '2026-10-02']))
            ->assertOk()->assertViewHas('total', 8)
            ->assertViewHas('daily', fn (LengthAwarePaginator $daily): bool => $daily->total() === 3);
    }

    public function test_daily_report_paginates_all_dates_beyond_ninety_and_keeps_filters(): void
    {
        $this->actingAs(User::factory()->admin()->createQuietly());
        $this->freezeTime();
        Lead::factory()->count(95)->sequence(fn (Sequence $sequence): array => [
            'created_at' => now()->subDays($sequence->index)->toDateTimeString(),
        ])->create();
        $filters = [
            'from' => now()->subDays(94)->toDateString(), 'to' => now()->toDateString(),
            'total_min' => '1', 'total_max' => '1', 'per_page' => '10',
        ];
        $this->get(route('admin.reports.index', [...$filters, 'page' => '2']))
            ->assertOk()->assertViewHas('total', 95)
            ->assertViewHas('daily', function (LengthAwarePaginator $daily) use ($filters): bool {
                $this->assertSame(95, $daily->total());
                $this->assertSame(10, $daily->perPage());
                $this->assertSame(10, $daily->lastPage());
                $this->assertSame(now()->subDays(10)->toDateString(), $daily->first()->day);
                $this->assertSame(now()->subDays(19)->toDateString(), $daily->last()->day);
                parse_str(parse_url($daily->nextPageUrl(), PHP_URL_QUERY), $nextQuery);
                $this->assertSame([...$filters, 'page' => '3'], $nextQuery);

                return true;
            })->assertSee('Số dòng / trang')->assertSee('Sắp xếp ngày', false);
        $this->get(route('admin.reports.index'))->assertOk()
            ->assertViewHas('daily', function (LengthAwarePaginator $daily): bool {
                return $daily->total() === 95 && $daily->perPage() === 20 && $daily->currentPage() === 1;
            });
    }

    public function test_audit_rejects_invalid_filters(): void
    {
        $this->actingAs(User::factory()->admin()->createQuietly());
        $this->getJson(route('admin.audit.index', [
            'from' => '2026-10-03', 'to' => '2026-10-01', 'user_id' => 'invalid', 'action' => ['invalid'],
            'subject_type' => 'unknown', 'subject_id' => '-1', 'changed_field' => ['invalid'], 'per_page' => '500',
        ]))->assertUnprocessable()->assertJsonValidationErrors([
            'to', 'user_id', 'action', 'subject_type', 'subject_id', 'changed_field', 'per_page',
        ]);
    }

    public function test_daily_report_rejects_invalid_ranges(): void
    {
        $this->actingAs(User::factory()->admin()->createQuietly());
        $this->getJson(route('admin.reports.index', [
            'from' => '2026-10-03', 'to' => '2026-10-01', 'total_min' => '-1', 'total_max' => '-2', 'per_page' => '500',
        ]))->assertUnprocessable()->assertJsonValidationErrors(['to', 'total_min', 'total_max', 'per_page']);
        $this->getJson(route('admin.reports.index', ['total_min' => '4', 'total_max' => '2']))
            ->assertUnprocessable()->assertJsonValidationErrors(['total_max']);
    }

    #[DataProvider('reportRoutes')]
    public function test_report_filters_require_authorized_user(string $routeName): void
    {
        $url = route($routeName, ['per_page' => '10']);
        $this->get($url)->assertRedirect(route('login'));
        $this->actingAs(User::factory()->editor()->createQuietly())->get($url)->assertForbidden();
        $this->actingAs(User::factory()->sales()->createQuietly())->get($url)->assertForbidden();
    }

    /** @return array<string, array{string}> */
    public static function reportRoutes(): array
    {
        return ['daily report' => ['admin.reports.index'], 'audit' => ['admin.audit.index']];
    }
}
