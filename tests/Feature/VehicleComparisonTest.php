<?php

namespace Tests\Feature;

use App\Models\Media;
use App\Models\Vehicle;
use App\Models\VehicleVariant;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class VehicleComparisonTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_empty_comparison_offers_only_active_vehicles_and_is_not_indexed(): void
    {
        $first = Vehicle::factory()->create(['name' => 'VinFast VF 6']);
        $second = Vehicle::factory()->create(['name' => 'VinFast VF 7']);
        $inactive = Vehicle::factory()->create(['name' => 'Mẫu xe chưa công bố', 'is_active' => false]);

        $response = $this->get(route('vehicles.compare'))->assertOk()
            ->assertViewIs('site.vehicle-comparison')
            ->assertViewHas('firstVehicle', null)
            ->assertViewHas('secondVehicle', null)
            ->assertViewHas('comparisonRows', [])
            ->assertDontSee($inactive->name)
            ->assertViewHas('comparisonOptions', function (Collection $options) use ($first, $second): bool {
                return $options->pluck('id')->all() === [$first->id, $second->id]
                    && array_keys($options->first()->getAttributes()) === ['id', 'name', 'slug'];
            });
        $document = $this->document($response);

        $this->assertSame('noindex,follow',
            $this->element($document, '//meta[@name="robots"]')->getAttribute('content'));
        $this->assertSame(route('vehicles.compare'),
            $this->element($document, '//link[@rel="canonical"]')->getAttribute('href'));
        $this->assertSame('get', strtolower($this->element($document, '//form[@method="GET" or @method="get"]')
            ->getAttribute('method')));
        $this->assertCount(1, $document->query('//label[@for=//select[@name="first"]/@id]'));
        $this->assertCount(1, $document->query('//label[@for=//select[@name="second"]/@id]'));
    }

    public function test_vehicle_detail_can_preselect_the_first_vehicle_without_requiring_a_second(): void
    {
        $vehicle = Vehicle::factory()->create(['specifications' => ['Số chỗ' => '5']]);
        $url = route('vehicles.compare', ['first' => $vehicle->slug]);

        $this->get(route('vehicles.show', $vehicle->slug))->assertOk()->assertSee($url, false);
        $response = $this->get($url)->assertOk()
            ->assertViewHas('firstVehicle', fn (Vehicle $first): bool => $first->is($vehicle))
            ->assertViewHas('secondVehicle', null)
            ->assertViewHas('comparisonRows', [
                ['label' => 'Số chỗ', 'first' => '5', 'second' => null, 'different' => true],
            ]);
        $document = $this->document($response);
        $this->assertSame($vehicle->slug,
            $this->element($document, '//select[@name="first"]/option[@selected]')->getAttribute('value'));
    }

    public function test_comparison_unions_specification_labels_and_preserves_zero_and_missing_values(): void
    {
        $media = Media::factory()->create();
        $first = Vehicle::factory()->create([
            'media_id' => $media->id,
            'specifications' => ['Số chỗ' => '5', 'Dung tích' => 0, 'Mô tả' => "  theo \t phiên\n bản  ",
                'Phạm vi' => '450 km', 'Đang cập nhật' => '', 'Trống' => null],
        ]);
        $second = Vehicle::factory()->create([
            'specifications' => ['Số chỗ' => '5', 'Dung tích' => '0', 'Mô tả' => 'theo phiên bản',
                'Phạm vi' => '480 km', 'Đang cập nhật' => ' ', 'Công suất' => '150 kW'],
        ]);
        $variant = VehicleVariant::factory()->create(['vehicle_id' => $first->id, 'price' => 650000000]);
        $rows = [
            ['label' => 'Số chỗ', 'first' => '5', 'second' => '5', 'different' => false],
            ['label' => 'Dung tích', 'first' => '0', 'second' => '0', 'different' => false],
            ['label' => 'Mô tả', 'first' => "  theo \t phiên\n bản  ",
                'second' => 'theo phiên bản', 'different' => false],
            ['label' => 'Phạm vi', 'first' => '450 km', 'second' => '480 km', 'different' => true],
            ['label' => 'Đang cập nhật', 'first' => null, 'second' => null, 'different' => false],
            ['label' => 'Trống', 'first' => null, 'second' => null, 'different' => false],
            ['label' => 'Công suất', 'first' => null, 'second' => '150 kW', 'different' => true],
        ];

        $this->get(route('vehicles.compare', ['first' => $first->slug, 'second' => $second->slug]))
            ->assertOk()->assertViewHas('comparisonRows', $rows)
            ->assertViewHas('firstVehicle', fn (Vehicle $vehicle): bool => $vehicle->is($first)
                && $vehicle->relationLoaded('media') && $vehicle->media->is($media)
                && $vehicle->relationLoaded('variants') && $vehicle->variants->contains($variant))
            ->assertViewHas('secondVehicle', fn (Vehicle $vehicle): bool => $vehicle->is($second)
                && $vehicle->relationLoaded('media') && $vehicle->relationLoaded('variants'))
            ->assertSee($variant->name)->assertSee('650.000.000');
    }

    public function test_non_string_specifications_keep_their_raw_json_values(): void
    {
        $first = Vehicle::factory()->create([
            'specifications' => ['Tùy chọn' => ['Eco', 'Plus'], 'Tính năng' => false],
        ]);

        $this->get(route('vehicles.compare', ['first' => $first->slug]))->assertOk()
            ->assertViewHas('comparisonRows', [
                ['label' => 'Tùy chọn', 'first' => '["Eco","Plus"]', 'second' => null, 'different' => true],
                ['label' => 'Tính năng', 'first' => 'false', 'second' => null, 'different' => true],
            ]);
    }

    public function test_inactive_vehicles_cannot_be_selected_on_either_side(): void
    {
        $inactive = Vehicle::factory()->create(['is_active' => false]);

        foreach (['first', 'second'] as $side) {
            $this->getJson(route('vehicles.compare', [$side => $inactive->slug]))
                ->assertUnprocessable()->assertJsonValidationErrors($side);
        }
    }

    public function test_duplicate_selection_redirects_with_a_localized_error_and_preserves_input(): void
    {
        $vehicle = Vehicle::factory()->create();
        $query = ['first' => $vehicle->slug, 'second' => $vehicle->slug];

        $this->from(route('vehicles.compare'))->get(route('vehicles.compare', $query))
            ->assertRedirect(route('vehicles.compare'))
            ->assertSessionHasErrors(['second' => 'Vui lòng chọn hai mẫu xe khác nhau.'])
            ->assertSessionHasInput('first', $vehicle->slug)
            ->assertSessionHasInput('second', $vehicle->slug);
        $this->getJson(route('en.vehicles.compare', $query))->assertUnprocessable()
            ->assertJsonPath('errors.second.0', 'Please choose two different models.');
    }

    #[DataProvider('invalidSelections')]
    /** @param array<string, mixed> $query */
    public function test_malformed_or_unknown_queries_fail_validation(array $query, string $side): void
    {
        $this->getJson(route('vehicles.compare', $query))
            ->assertUnprocessable()->assertJsonValidationErrors($side);
    }

    /** @return array<string, array{array<string, mixed>, string}> */
    public static function invalidSelections(): array
    {
        return [
            'first array' => [['first' => ['vf-7']], 'first'],
            'second array' => [['second' => ['vf-6']], 'second'],
            'long first' => [['first' => str_repeat('a', 181)], 'first'],
            'long second' => [['second' => str_repeat('b', 181)], 'second'],
            'unknown first' => [['first' => 'khong-ton-tai'], 'first'],
            'unknown second' => [['second' => 'khong-ton-tai'], 'second'],
        ];
    }

    public function test_canonical_ignores_tracking_while_language_switcher_preserves_vehicle_selection(): void
    {
        $first = Vehicle::factory()->create();
        $second = Vehicle::factory()->create();
        $selection = ['first' => $first->slug, 'second' => $second->slug];
        $query = $selection + ['utm_source' => 'comparison-test'];

        foreach (['' => 'vi', 'en.' => 'en'] as $prefix => $locale) {
            $response = $this->get(route($prefix.'vehicles.compare', $query))->assertOk()
                ->assertHeader('Content-Language', $locale);
            $document = $this->document($response);

            $this->assertSame(route($prefix.'vehicles.compare', $selection),
                $this->element($document, '//link[@rel="canonical"]')->getAttribute('href'));
            $this->assertSame($locale, $this->element($document, '//html')->getAttribute('lang'));
            foreach (['vi' => '', 'en' => 'en.'] as $language => $languagePrefix) {
                $url = route($languagePrefix.'vehicles.compare', $query);
                $this->assertSame($url,
                    $this->element($document, '//link[@hreflang="'.$language.'"]')->getAttribute('href'));
                $this->assertSame($url,
                    $this->element($document, '//a[@hreflang="'.$language.'"]')->getAttribute('href'));
            }
            $this->assertSame(route($prefix.'vehicles.compare'),
                $this->element($document, '//form[@method="GET" or @method="get"]')->getAttribute('action'));
        }
    }

    public function test_english_comparison_translates_interface_and_keeps_vehicle_links_in_english(): void
    {
        $first = Vehicle::factory()->create(['name' => 'VinFast VF 6']);
        $second = Vehicle::factory()->create(['name' => 'VinFast VF 7']);
        $response = $this->get(route('en.vehicles.compare', ['first' => $first->slug, 'second' => $second->slug]))
            ->assertOk()->assertSee('Compare vehicles')->assertSee('Get a quote')->assertSee('Book a test drive');
        $document = $this->document($response);

        $this->assertStringContainsString('Compare vehicles', $this->element($document, '//title')->textContent);
        foreach ([$first, $second] as $vehicle) {
            $this->assertHasLink($document, route('en.vehicles.show', $vehicle->slug));
            $testDriveUrl = route('en.contact', ['type' => 'test_drive', 'vehicle' => $vehicle->id]);
            $this->assertHasLink($document, $testDriveUrl, 'data-test-drive-open');
        }
        $response->assertViewHas('breadcrumbs', [
            ['label' => 'Home', 'url' => route('en.home')],
            ['label' => 'Vehicles', 'url' => route('en.vehicles.index')],
            ['label' => 'Compare vehicles'],
        ]);
    }

    public function test_specifications_precede_images_and_difference_rows_identify_missing_values(): void
    {
        $first = Vehicle::factory()->create(['specifications' => ['Số chỗ' => '5', 'Công suất' => '150 kW']]);
        $second = Vehicle::factory()->create(['specifications' => ['Số chỗ' => '5']]);
        $document = $this->document($this->get(route('vehicles.compare', [
            'first' => $first->slug, 'second' => $second->slug,
        ]))->assertOk());
        $table = '//table[contains(@class, "vehicle-comparison-table")]';

        $this->assertCount(2, $document->query($table.'/following::article'));
        $this->assertCount(3, $document->query($table.'/thead/tr/th[@scope="col"]'));
        $this->assertCount(1, $document->query('//tr[@data-different="false"]/th[contains(., "Số chỗ")]'));
        $missing = $this->element($document, '//tr[@data-different="true"]/td[2]');
        $this->assertSame('Chưa cập nhật', trim($missing->textContent));
        $this->assertCount(1, $document->query('//tr[@data-different="true"]/th/span[contains(., "Khác nhau")]'));
    }

    public function test_comparison_escapes_vehicle_variant_and_specification_content(): void
    {
        $first = Vehicle::factory()->create([
            'name' => '<script>vehicleName()</script>',
            'specifications' => ['<img src=x onerror=label()>' => '<script>value()</script>'],
        ]);
        $second = Vehicle::factory()->create();
        $variant = VehicleVariant::factory()->create([
            'vehicle_id' => $first->id, 'name' => '<svg onload=variant()>',
        ]);

        $response = $this->get(route('vehicles.compare', [
            'first' => $first->slug, 'second' => $second->slug,
        ]))->assertOk()->assertSee($first->name)->assertSee($variant->name)
            ->assertSee('<img src=x onerror=label()>')->assertSee('<script>value()</script>');
        $document = $this->document($response);
        $this->assertCount(0, $document->query('//main//script | //main//*[@onerror or @onload]'));
    }

    public function test_empty_specifications_and_variants_have_explicit_placeholders(): void
    {
        $first = Vehicle::factory()->create(['specifications' => null]);
        $second = Vehicle::factory()->create(['specifications' => []]);
        $document = $this->document($this->get(route('vehicles.compare', [
            'first' => $first->slug, 'second' => $second->slug,
        ]))->assertOk()->assertSee('Thông số của hai mẫu xe đang được cập nhật.'));

        $this->assertCount(2, $document->query('//table//li[contains(., "Chưa cập nhật")]'));
        $this->assertCount(1, $document->query('//table//td[@colspan="3"]'));
    }

    public function test_prices_use_each_models_lowest_known_variant_and_preserve_zero(): void
    {
        $first = Vehicle::factory()->create();
        $second = Vehicle::factory()->create();
        VehicleVariant::factory()->create(['vehicle_id' => $first->id, 'price' => null]);
        VehicleVariant::factory()->create(['vehicle_id' => $first->id, 'price' => 600000000]);
        VehicleVariant::factory()->create(['vehicle_id' => $first->id, 'price' => 500000000]);
        VehicleVariant::factory()->create(['vehicle_id' => $second->id, 'price' => 0]);
        $document = $this->document($this->get(route('vehicles.compare', [
            'first' => $first->slug, 'second' => $second->slug,
        ]))->assertOk());
        $cards = $document->query('//article');

        $this->assertStringContainsString('500.000.000 VNĐ', $cards->item(0)->textContent);
        $this->assertStringNotContainsString('600.000.000 VNĐ', $cards->item(0)->textContent);
        $this->assertStringContainsString('0 VNĐ', $cards->item(1)->textContent);
        $this->assertStringNotContainsString('Liên hệ nhận báo giá', $cards->item(1)->textContent);
    }

    public function test_second_vehicle_only_is_preselected_without_rendering_an_incomplete_table(): void
    {
        $vehicle = Vehicle::factory()->create();
        $document = $this->document($this->get(route('vehicles.compare', ['second' => $vehicle->slug]))
            ->assertOk()->assertViewHas('firstVehicle', null)
            ->assertViewHas('secondVehicle', fn (Vehicle $second): bool => $second->is($vehicle)));

        $this->assertSame($vehicle->slug,
            $this->element($document, '//select[@name="second"]/option[@selected]')->getAttribute('value'));
        $this->assertCount(0, $document->query('//table[contains(@class, "vehicle-comparison-table")]'));
        $this->assertCount(2, $document->query('//article'));
    }

    public function test_fewer_than_two_active_models_disables_comparison_and_explains_why(): void
    {
        Vehicle::factory()->create(['is_active' => false]);
        foreach ([0, 1] as $activeCount) {
            if ($activeCount === 1) {
                Vehicle::factory()->create();
            }
            $document = $this->document($this->get(route('vehicles.compare'))->assertOk()
                ->assertSee('Cần ít nhất hai mẫu xe đang được hiển thị để so sánh.'));
            $this->assertCount(1, $document->query('//form//button[@type="submit" and @disabled]'));
            $this->assertCount(0, $document->query('//table[contains(@class, "vehicle-comparison-table")]'));
        }
    }

    private function document(TestResponse $response): DOMXPath
    {
        $document = new DOMDocument;
        @$document->loadHTML('<?xml encoding="UTF-8">'.$response->getContent());

        return new DOMXPath($document);
    }

    private function element(DOMXPath $document, string $query): DOMElement
    {
        $element = $document->query($query)->item(0);
        $this->assertInstanceOf(DOMElement::class, $element, 'Missing element '.$query);

        return $element;
    }

    private function assertHasLink(DOMXPath $document, string $url, ?string $attribute = null): void
    {
        $links = $document->query('//a[@href="'.$url.'"]'.($attribute ? '[@'.$attribute.']' : ''));
        $this->assertGreaterThan(0, $links->count(), 'Missing link '.$url);
    }
}
