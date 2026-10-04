<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\Promotion;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CatalogTableFiltersTest extends TestCase
{
    use LazilyRefreshDatabase;

    #[DataProvider('catalogs')]
    public function test_catalog_filters_each_column_and_combines_filters(
        string $resource,
        string $model,
        string $titleColumn,
    ): void {
        $this->actingAs(User::factory()->manager()->create());
        $matching = $model::factory()->create([
            $titleColumn => 'Danh sách mục tiêu', 'slug' => 'muc-tieu-an', 'is_active' => false,
        ]);
        $visible = $model::factory()->create([
            $titleColumn => 'Danh sách mục tiêu đang bật', 'slug' => 'muc-tieu-bat', 'is_active' => true,
        ]);
        $other = $model::factory()->create([
            $titleColumn => 'Nội dung khác', 'slug' => 'noi-dung-khac', 'is_active' => false,
        ]);

        foreach ([
            [['q' => 'mục tiêu'], [$visible->id, $matching->id]],
            [['slug' => 'muc-tieu'], [$visible->id, $matching->id]],
            [['is_active' => '0'], [$other->id, $matching->id]],
            [['is_active' => '1'], [$visible->id]],
            [['q' => 'mục tiêu', 'slug' => 'muc-tieu', 'is_active' => '0'], [$matching->id]],
        ] as [$filters, $expectedIds]) {
            $this->get(route('admin.'.$resource.'.index', $filters))
                ->assertOk()
                ->assertViewHas(
                    'records',
                    fn (LengthAwarePaginator $records): bool => $records->pluck('id')->all() === $expectedIds,
                );
        }
    }

    #[DataProvider('catalogs')]
    public function test_catalog_pagination_preserves_filters_and_reset_restores_all_records(
        string $resource,
        string $model,
        string $titleColumn,
    ): void {
        $this->actingAs(User::factory()->admin()->create());
        $this->freezeTime();
        $matching = $model::factory()->count(23)->sequence(fn (Sequence $sequence): array => [
            $titleColumn => 'Nội dung mục tiêu '.$sequence->index,
            'slug' => 'muc-tieu-'.$sequence->index,
            'is_active' => false,
        ])->create();
        $model::factory()->count(2)->create(['is_active' => true]);
        $filters = ['q' => 'mục tiêu', 'slug' => 'muc-tieu', 'is_active' => '0', 'per_page' => '10'];
        $response = $this->get(route('admin.'.$resource.'.index', [...$filters, 'page' => '2']));

        $response->assertOk()->assertViewHas('records', function (LengthAwarePaginator $records) use (
            $matching,
            $filters,
        ): bool {
            $this->assertSame(23, $records->total());
            $this->assertSame(10, $records->perPage());
            $this->assertSame(2, $records->currentPage());
            $this->assertSame($matching->pluck('id')->reverse()->values()->slice(10, 10)->values()->all(),
                $records->pluck('id')->all());
            parse_str(parse_url($records->nextPageUrl(), PHP_URL_QUERY), $nextQuery);
            $this->assertSame([...$filters, 'page' => '3'], $nextQuery);

            return true;
        });
        $response->assertSee('form="table-filters"', false)
            ->assertSee('name="per_page"', false)
            ->assertSee('Xóa bộ lọc')
            ->assertSee('href="'.route('admin.'.$resource.'.index').'"', false);

        $this->get(route('admin.'.$resource.'.index'))
            ->assertOk()
            ->assertViewHas('records', function (LengthAwarePaginator $records): bool {
                return $records->total() === 25 && $records->perPage() === 20 && $records->currentPage() === 1;
            });
    }

    #[DataProvider('catalogs')]
    public function test_catalog_text_filters_accept_zero_and_empty_results_show_recovery(
        string $resource,
        string $model,
        string $titleColumn,
    ): void {
        $this->actingAs(User::factory()->manager()->create());
        $matching = $model::factory()->create([$titleColumn => 'Nội dung 0', 'slug' => 'noi-dung-0']);
        $model::factory()->create([$titleColumn => 'Nội dung khác', 'slug' => 'noi-dung-khac']);

        $this->get(route('admin.'.$resource.'.index', ['q' => '0', 'slug' => '0']))
            ->assertOk()
            ->assertViewHas(
                'records',
                fn (LengthAwarePaginator $records): bool => $records->pluck('id')->all() === [$matching->id],
            );
        $this->get(route('admin.'.$resource.'.index', ['slug' => 'khong-co-ket-qua']))
            ->assertOk()
            ->assertSee('Chưa có nội dung phù hợp')
            ->assertSee('Xóa bộ lọc')
            ->assertViewHas('records', fn (LengthAwarePaginator $records): bool => $records->total() === 0);
    }

    #[DataProvider('catalogs')]
    public function test_catalog_rejects_invalid_filter_values(
        string $resource,
        string $model,
        string $titleColumn,
    ): void {
        $this->actingAs(User::factory()->admin()->create());
        $this->getJson(route('admin.'.$resource.'.index', [
            'q' => ['bad'], 'slug' => str_repeat('x', 161), 'is_active' => 'hidden', 'per_page' => '500',
        ]))->assertUnprocessable()->assertJsonValidationErrors(['q', 'slug', 'is_active', 'per_page']);
    }

    #[DataProvider('catalogs')]
    public function test_catalog_filters_require_authorized_user(
        string $resource,
        string $model,
        string $titleColumn,
    ): void {
        $url = route('admin.'.$resource.'.index', ['is_active' => '0', 'per_page' => '10']);
        $this->get($url)->assertRedirect(route('login'));
        $this->actingAs(User::factory()->editor()->create())->get($url)->assertForbidden();
        $this->actingAs(User::factory()->sales()->create())->get($url)->assertForbidden();
    }

    /** @return array<string, array{string, class-string, string}> */
    public static function catalogs(): array
    {
        return [
            'promotions' => ['promotions', Promotion::class, 'title'],
            'pages' => ['pages', Page::class, 'title'],
            'vehicles' => ['vehicles', Vehicle::class, 'name'],
        ];
    }
}
