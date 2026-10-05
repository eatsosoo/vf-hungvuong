<?php

namespace Tests\Feature;

use App\Actions\Promotions\SavePromotion;
use App\Actions\Vehicles\SaveVehicle;
use App\Models\Category;
use App\Models\Lead;
use App\Models\Media;
use App\Models\Page;
use App\Models\Post;
use App\Models\Promotion;
use App\Models\Tag;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleColor;
use App\Models\VehicleVariant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdminRecordLifecycleTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    #[DataProvider('recordTypes')]
    /** @param class-string<Model> $modelClass */
    public function test_blank_slugs_are_generated_from_the_trimmed_record_name(
        string $resource,
        string $modelClass,
        string $nameField,
        ?string $kind,
    ): void {
        $admin = User::factory()->admin()->create();
        $data = $this->recordData($resource, $nameField, '  Dòng xe thử nghiệm  ', '');

        $this->actingAs($admin)->post($this->recordUrl($resource, 'store', $kind), $data)
            ->assertSessionHasNoErrors()->assertSessionHas('success');

        $record = $modelClass::query()->sole();
        $this->assertSame('Dòng xe thử nghiệm', $record->{$nameField});
        $this->assertSame('dong-xe-thu-nghiem', $record->slug);
    }

    #[DataProvider('recordTypes')]
    /** @param class-string<Model> $modelClass */
    public function test_duplicate_names_and_slugs_are_rejected_without_creating_another_record(
        string $resource,
        string $modelClass,
        string $nameField,
        ?string $kind,
    ): void {
        $admin = User::factory()->admin()->create();
        $existing = $modelClass::factory()->create([$nameField => 'Existing record', 'slug' => 'existing-record']);
        $data = $this->recordData($resource, $nameField, $existing->{$nameField}, $existing->slug);

        $this->actingAs($admin)->post($this->recordUrl($resource, 'store', $kind), $data)
            ->assertSessionHasErrors([$nameField, 'slug']);

        $this->assertModelExists($existing);
        $this->assertDatabaseCount($existing->getTable(), 1);
    }

    #[DataProvider('recordTypes')]
    /** @param class-string<Model> $modelClass */
    public function test_update_accepts_unchanged_name_and_slug_and_can_regenerate_a_blank_slug(
        string $resource,
        string $modelClass,
        string $nameField,
        ?string $kind,
    ): void {
        $admin = User::factory()->admin()->create();
        $record = $modelClass::factory()->create();
        $data = $this->recordData($resource, $nameField, $record->{$nameField}, $record->slug);
        $url = $this->recordUrl($resource, 'update', $kind, $record);

        $this->actingAs($admin)->put($url, $data)->assertSessionHasNoErrors()->assertSessionHas('success');
        $this->assertSame($record->slug, $record->fresh()->slug);
        $this->put($url, [...$data, $nameField => 'Tên mới sau chỉnh sửa', 'slug' => ''])
            ->assertSessionHasNoErrors()->assertSessionHas('success');

        $this->assertSame('ten-moi-sau-chinh-sua', $record->fresh()->slug);
        $this->assertDatabaseCount($record->getTable(), 1);
    }

    /** @return array<string, array{string, class-string<Model>, string, ?string}> */
    public static function recordTypes(): array
    {
        return [
            'vehicle' => ['vehicles', Vehicle::class, 'name', null],
            'promotion' => ['promotions', Promotion::class, 'title', null],
            'page' => ['pages', Page::class, 'title', null],
            'post' => ['posts', Post::class, 'title', null],
            'category' => ['taxonomies', Category::class, 'name', 'categories'],
            'tag' => ['taxonomies', Tag::class, 'name', 'tags'],
        ];
    }

    public function test_vehicle_update_replaces_variants_and_colors_and_can_clear_both_collections(): void
    {
        $manager = User::factory()->manager()->create();
        $vehicle = Vehicle::factory()->create(['specifications' => ['Số chỗ' => 5]]);
        $other = Vehicle::factory()->create();
        $keptVariant = VehicleVariant::factory()->create(['vehicle_id' => $vehicle->id, 'name' => 'Eco']);
        $removedVariant = VehicleVariant::factory()->create(['vehicle_id' => $vehicle->id, 'name' => 'Old variant']);
        $removedColor = VehicleColor::factory()->create(['vehicle_id' => $vehicle->id, 'name' => 'Old color']);
        $otherVariant = VehicleVariant::factory()->create(['vehicle_id' => $other->id]);
        $media = Media::factory()->create();
        $data = [
            'name' => $vehicle->name, 'slug' => $vehicle->slug, 'is_active' => true,
            'specifications' => '{"Số chỗ":7}',
            'variants' => [['name' => 'Eco', 'price' => 0], ['name' => 'Plus', 'price' => 800000000]],
            'colors' => [['name' => '  New color  ', 'hex' => '#112233',
                'secondary_hex' => '#ffffff', 'media_id' => $media->id]],
        ];

        $this->actingAs($manager)->put(route('admin.vehicles.update', $vehicle), $data)
            ->assertSessionHasNoErrors()->assertSessionHas('success');

        $this->assertSame(['Số chỗ' => 7], $vehicle->fresh()->specifications);
        $this->assertSame('0', $keptVariant->fresh()->price);
        $this->assertModelMissing($removedVariant);
        $this->assertModelMissing($removedColor);
        $this->assertModelExists($otherVariant);
        $this->assertSame(2, $vehicle->variants()->count());
        $color = $vehicle->colors()->sole();
        $this->assertSame('New color', $color->name);
        $this->assertSame('#ffffff', $color->secondary_hex);
        $this->assertSame($media->id, $color->media_id);

        $this->put(route('admin.vehicles.update', $vehicle), [
            'name' => $vehicle->name, 'slug' => $vehicle->slug, 'is_active' => true,
            'specifications' => '', 'variants' => [], 'colors' => [],
        ])->assertSessionHasNoErrors()->assertSessionHas('success');

        $this->assertNull($vehicle->fresh()->specifications);
        $this->assertSame(0, $vehicle->variants()->count());
        $this->assertSame(0, $vehicle->colors()->count());
        $this->assertModelExists($otherVariant);
        $this->assertModelExists($media);
    }

    public function test_partial_color_rows_keep_indexes_and_invalid_nested_data_does_not_change_the_vehicle(): void
    {
        $manager = User::factory()->manager()->create();
        $vehicle = Vehicle::factory()->create();
        $color = VehicleColor::factory()->create(['vehicle_id' => $vehicle->id]);
        $data = [
            'name' => 'Rejected partial save', 'slug' => $vehicle->slug, 'is_active' => true,
            'colors' => [3 => ['name' => '', 'hex' => '', 'secondary_hex' => '', 'media_id' => ''],
                8 => ['name' => '', 'hex' => '#112233']],
        ];

        $this->actingAs($manager)->put(route('admin.vehicles.update', $vehicle), $data)
            ->assertSessionHasErrors('colors.8.name')->assertSessionDoesntHaveErrors('colors.3.name')
            ->assertSessionHasInput('colors.8.hex', '#112233');

        $this->assertSame($vehicle->name, $vehicle->fresh()->name);
        $this->assertModelExists($color);
        $this->assertSame(1, $vehicle->colors()->count());
    }

    public function test_duplicate_color_names_and_unsafe_roof_hex_reject_the_entire_save(): void
    {
        $manager = User::factory()->manager()->create();
        $this->actingAs($manager)->post(route('admin.vehicles.store'), [
            'name' => 'Invalid color vehicle', 'slug' => 'invalid-color-vehicle', 'is_active' => true,
            'colors' => [['name' => 'White', 'hex' => '#ffffff'],
                ['name' => 'white', 'hex' => '#ffffff', 'secondary_hex' => 'javascript:alert(1)']],
        ])->assertSessionHasErrors(['colors.0.name', 'colors.1.name', 'colors.1.secondary_hex']);

        $this->assertDatabaseCount('vehicles', 0);
        $this->assertDatabaseCount('vehicle_colors', 0);
    }

    public function test_vehicle_deletion_clears_relations_without_deleting_leads_posts_or_media(): void
    {
        $manager = User::factory()->manager()->create();
        $media = Media::factory()->create();
        $vehicle = Vehicle::factory()->create(['media_id' => $media->id]);
        $variant = VehicleVariant::factory()->create(['vehicle_id' => $vehicle->id]);
        $color = VehicleColor::factory()->create(['vehicle_id' => $vehicle->id, 'media_id' => $media->id]);
        $lead = Lead::factory()->create(['vehicle_id' => $vehicle->id, 'vehicle_variant_id' => $variant->id]);
        $post = Post::factory()->create();
        $post->vehicles()->attach($vehicle);
        $promotion = Promotion::factory()->create();
        $promotion->vehicles()->attach($vehicle);

        $this->actingAs($manager)->delete(route('admin.vehicles.destroy', $vehicle))
            ->assertRedirect(route('admin.vehicles.index'))->assertSessionHas('success');

        $this->assertModelMissing($vehicle);
        $this->assertModelMissing($variant);
        $this->assertModelMissing($color);
        $this->assertModelExists($media);
        $this->assertModelExists($post);
        $this->assertModelExists($promotion);
        $this->assertNull($lead->fresh()->vehicle_id);
        $this->assertNull($lead->fresh()->vehicle_variant_id);
        $this->assertDatabaseCount('post_vehicle', 0);
        $this->assertDatabaseCount('promotion_vehicle', 0);
    }

    public function test_invalid_promotion_dates_and_duplicate_vehicles_do_not_replace_existing_relations(): void
    {
        $manager = User::factory()->manager()->create();
        $vehicle = Vehicle::factory()->create();
        $promotion = Promotion::factory()->create();
        $promotion->vehicles()->attach($vehicle);

        $this->actingAs($manager)->put(route('admin.promotions.update', $promotion), [
            'title' => 'Rejected update', 'slug' => $promotion->slug, 'body' => 'Rejected body', 'is_active' => true,
            'starts_at' => '2026-10-10 08:00:00', 'ends_at' => '2026-10-09 08:00:00',
            'vehicles' => [$vehicle->id, $vehicle->id],
        ])->assertSessionHasErrors(['ends_at', 'vehicles.0', 'vehicles.1']);

        $this->assertSame($promotion->title, $promotion->fresh()->title);
        $this->assertSame([$vehicle->id], $promotion->vehicles()->pluck('vehicles.id')->all());
    }

    public function test_category_and_tag_deletion_detaches_the_taxonomies_without_deleting_the_article(): void
    {
        $editor = User::factory()->editor()->create();
        $category = Category::factory()->create();
        $tag = Tag::factory()->create();
        $post = Post::factory()->create(['category_id' => $category->id]);
        $post->tags()->attach($tag);

        $this->actingAs($editor)->delete(route('admin.taxonomies.destroy', ['categories', $category->id]))
            ->assertSessionHas('success');
        $this->delete(route('admin.taxonomies.destroy', ['tags', $tag->id]))->assertSessionHas('success');

        $this->assertModelExists($post);
        $this->assertNull($post->fresh()->category_id);
        $this->assertDatabaseCount('post_tag', 0);
    }

    public function test_vehicle_save_rolls_back_parent_and_children_when_a_color_relation_fails(): void
    {
        $vehicle = Vehicle::factory()->create();
        $previousName = $vehicle->name;
        $variant = VehicleVariant::factory()->create(['vehicle_id' => $vehicle->id, 'name' => 'Eco', 'price' => 100]);
        $color = VehicleColor::factory()->create(['vehicle_id' => $vehicle->id, 'name' => 'Original color']);

        try {
            app(SaveVehicle::class)->handle([
                'name' => 'Failed replacement', 'slug' => $vehicle->slug, 'is_active' => true,
                'variants' => [['name' => 'Eco', 'price' => 200]],
                'colors' => [['name' => 'Replacement color', 'media_id' => 999999]],
            ], $vehicle);
            $this->fail('An unknown color image must fail the transaction.');
        } catch (QueryException) {
            $this->assertSame($previousName, $vehicle->fresh()->name);
            $this->assertSame('100', $variant->fresh()->price);
            $this->assertModelExists($color);
            $this->assertSame(1, $vehicle->colors()->count());
        }
    }

    public function test_promotion_save_rolls_back_content_and_pivot_replacement_when_a_vehicle_relation_fails(): void
    {
        $originalVehicle = Vehicle::factory()->create();
        $replacementVehicle = Vehicle::factory()->create();
        $promotion = Promotion::factory()->create();
        $promotion->vehicles()->attach($originalVehicle);
        $previousTitle = $promotion->title;

        try {
            app(SavePromotion::class)->handle([
                'title' => 'Failed replacement', 'body' => 'Failed content',
                'vehicles' => [$replacementVehicle->id, 999999],
            ], $promotion);
            $this->fail('An unknown promotion vehicle must fail the transaction.');
        } catch (QueryException) {
            $this->assertSame($previousTitle, $promotion->fresh()->title);
            $this->assertSame('Thông tin ưu đãi', $promotion->fresh()->body);
            $this->assertSame([$originalVehicle->id], $promotion->vehicles()->pluck('vehicles.id')->all());
        }
    }

    /** @return array<string, mixed> */
    private function recordData(string $resource, string $nameField, string $name, string $slug): array
    {
        return [$nameField => $name, 'slug' => $slug] + match ($resource) {
            'vehicles' => ['is_active' => true],
            'promotions' => ['body' => 'Promotion content', 'is_active' => true,
                'starts_at' => '2026-10-01 08:00:00', 'ends_at' => '2026-10-31 18:00:00'],
            'pages' => ['body' => 'Page content', 'is_active' => true],
            'posts' => ['body' => 'Post content', 'status' => 'draft'],
            default => [],
        };
    }

    private function recordUrl(string $resource, string $action, ?string $kind, ?Model $record = null): string
    {
        $parameters = $kind === null ? [] : [$kind];
        if ($record !== null) {
            $parameters[] = $record->getKey();
        }

        return route('admin.'.$resource.'.'.$action, $parameters);
    }
}
