<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Media;
use App\Models\Post;
use App\Models\Promotion;
use App\Models\User;
use App\Models\Vehicle;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AdminInterfaceTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_record_table_contains_metadata_icon_actions_and_pagination_in_one_block(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $promotion = Promotion::factory()->create([
            'created_at' => '2026-10-01 08:30:00', 'updated_at' => '2026-10-03 14:45:00',
        ]);
        $response = $this->get(route('admin.promotions.index'))->assertOk();
        $document = $this->document($response->getContent());
        $block = '//div[contains(concat(" ", normalize-space(@class), " "), " table-block ")]';
        $this->assertCount(1, $document->query($block.'//table'));
        $this->assertCount(1, $document->query($block.'//nav[@aria-label="Phân trang"]'));
        $this->assertCount(2, $document->query($block.'//tbody//time[@datetime]'));
        $response->assertSee('#'.$promotion->id)->assertSee('01/10/2026 08:30')->assertSee('03/10/2026 14:45');
        foreach (['Sửa', 'Xóa'] as $action) {
            $controls = $document->query($block.'//tbody//*[@aria-label="'.$action.' #'.$promotion->id.'"]');
            $this->assertCount(1, $controls);
            $this->assertSame('', trim($controls->item(0)->textContent));
            $this->assertCount(1, $document->query('.//svg[@aria-hidden="true"]', $controls->item(0)));
        }
        $this->assertCount(1, $document->query($block.'//form[@data-confirm]//input[@value="DELETE"]'));
    }

    public function test_post_pagination_form_is_outside_bulk_form_in_the_same_block(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $post = Post::factory()->create();
        $document = $this->document($this->get(route('admin.posts.index'))->assertOk()->getContent());
        $block = '//div[contains(concat(" ", normalize-space(@class), " "), " table-block ")]';
        $this->assertCount(1, $document->query($block.'//form[@method="post"]//input[@name="ids[]"]'));
        $this->assertCount(1, $document->query($block.'//form[@method="get"]//select[@name="per_page"]'));
        $this->assertCount(0, $document->query($block.'//form[@method="post"]//select[@name="per_page"]'));
        $this->assertCount(1, $document->query($block.'//a[@aria-label="Xem trước #'.$post->id.'"]//svg'));
    }

    private function document(string $html): DOMXPath
    {
        $document = new DOMDocument;
        @$document->loadHTML('<?xml encoding="UTF-8">'.$html);

        return new DOMXPath($document);
    }

    public function test_lead_navigation_has_one_active_destination_for_each_filter(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        foreach ([
            '' => 'Khách hàng & yêu cầu',
            'quote' => 'Yêu cầu báo giá',
            'test_drive' => 'Lịch lái thử',
        ] as $type => $label) {
            $response = $this->get(route('admin.leads.index', $type ? ['type' => $type] : []))->assertOk();
            $navigation = $this->document($response->getContent());
            $activeLinks = $navigation->query('//nav[@aria-label="Quản trị"]//a[@aria-current="page"]');
            $this->assertCount(1, $activeLinks);
            $this->assertStringContainsString($label, $activeLinks->item(0)->textContent);
        }
    }

    public function test_sales_navigation_only_shows_permitted_destinations(): void
    {
        $response = $this->actingAs(User::factory()->sales()->create())->get(route('admin.dashboard'))->assertOk();
        $navigation = $this->document($response->getContent());
        $this->assertCount(0, $navigation->query('//nav[@aria-label="Quản trị"]//a[contains(@href, "/users")]'));
        $this->assertCount(0, $navigation->query('//nav[@aria-label="Quản trị"]//a[contains(@href, "/posts")]'));
        $this->assertCount(1, $navigation->query('//nav[@aria-label="Quản trị"]//a[contains(@href, "/password")]'));
        $this->get(route('admin.users.index'))->assertForbidden();
    }

    public function test_promotion_editor_preserves_dates_body_and_selected_vehicles(): void
    {
        $vehicle = Vehicle::factory()->create();
        $promotion = Promotion::factory()->create([
            'body' => "## Ưu đãi\n\nNội dung **Markdown**.",
            'starts_at' => '2026-10-03 08:30:00',
            'ends_at' => '2026-10-31 17:00:00',
        ]);
        $promotion->vehicles()->attach($vehicle);
        $response = $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.promotions.edit', $promotion))->assertOk();
        $fields = $this->document($response->getContent());
        $this->assertSame('2026-10-03T08:30', $fields->query('//input[@name="starts_at"]')->item(0)
            ->getAttribute('value'));
        $this->assertSame('datetime-local', $fields->query('//input[@name="starts_at"]')->item(0)
            ->getAttribute('type'));
        $this->assertSame($promotion->body, $fields->query('//textarea[@name="body"]')->item(0)->textContent);
        $this->assertCount(1, $fields->query('//input[@name="vehicles[]"][@checked]'));
        $this->assertSame((string) $vehicle->id,
            $fields->query('//input[@name="vehicles[]"][@checked]')->item(0)->getAttribute('value'));
        $checkbox = $fields->query('//input[@name="vehicles[]"][@checked]')->item(0);
        $this->assertCount(1, $fields->query('//label[@for="'.$checkbox->getAttribute('id').'"]'));
    }

    public function test_failed_promotion_form_retains_edited_values_and_vehicle_selection(): void
    {
        $originalVehicle = Vehicle::factory()->create();
        $replacementVehicle = Vehicle::factory()->create();
        $promotion = Promotion::factory()->create();
        $promotion->vehicles()->attach($originalVehicle);
        $this->actingAs(User::factory()->admin()->create());

        foreach ([[$replacementVehicle->id], []] as $selectedVehicleIds) {
            $data = [
                'title' => 'Ưu đãi vừa chỉnh sửa',
                'slug' => $promotion->slug,
                'body' => '**Nội dung sửa chưa lưu**.',
                'starts_at' => '2026-10-10T08:00',
                'ends_at' => '2026-10-09T08:00',
                'is_active' => '0',
                'vehicle_selection_present' => '1',
            ];
            if ($selectedVehicleIds !== []) {
                $data['vehicles'] = $selectedVehicleIds;
            }

            $response = $this->followingRedirects()->from(route('admin.promotions.edit', $promotion))
                ->put(route('admin.promotions.update', $promotion), $data)
                ->assertOk();
            $fields = $this->document($response->getContent());
            $checkedVehicles = $fields->query('//input[@name="vehicles[]"][@checked]');
            $this->assertCount(count($selectedVehicleIds), $checkedVehicles);
            if ($selectedVehicleIds !== []) {
                $this->assertSame((string) $replacementVehicle->id, $checkedVehicles->item(0)->getAttribute('value'));
            }
            $this->assertSame($data['title'], $fields->query('//input[@name="title"]')->item(0)->getAttribute('value'));
            $this->assertSame($data['body'], $fields->query('//textarea[@name="body"]')->item(0)->textContent);
            $endsAt = $fields->query('//input[@name="ends_at"]')->item(0);
            $this->assertSame($data['ends_at'], $endsAt->getAttribute('value'));
            $this->assertSame('true', $endsAt->getAttribute('aria-invalid'));
            $this->assertSame([$originalVehicle->id], $promotion->fresh()->vehicles->modelKeys());
        }
    }

    public function test_failed_vehicle_form_retains_nested_values_and_links_inline_errors(): void
    {
        $response = $this->followingRedirects()->actingAs(User::factory()->admin()->create())
            ->from(route('admin.vehicles.create'))->post(route('admin.vehicles.store'), [
                'name' => 'VF 7', 'slug' => 'vf-7', 'is_active' => '1',
                'variants' => [['name' => 'Eco', 'price' => '-1']],
            ])->assertOk();
        $fields = $this->document($response->getContent());
        $price = $fields->query('//input[@name="variants[0][price]"]')->item(0);
        $this->assertSame('-1', $price->getAttribute('value'));
        $this->assertSame('true', $price->getAttribute('aria-invalid'));
        $error = $fields->query('//*[@id="'.$price->getAttribute('aria-describedby').'"]');
        $this->assertCount(1, $error);
        $this->assertCount(1, $fields->query('//a[@data-error-field="variants.0.price"]'));
        $this->assertCount(1, $fields->query('//label[@for="'.$price->getAttribute('id').'"]'));
        $this->assertDatabaseCount('vehicles', 0);
    }

    public function test_repeated_forms_have_unique_field_ids_and_labels(): void
    {
        Category::factory()->count(2)->create();
        Media::factory()->count(2)->create();
        $this->actingAs(User::factory()->admin()->create());
        foreach ([route('admin.taxonomies.index', 'categories'), route('admin.media.index')] as $route) {
            $response = $this->get($route)->assertOk();
            $fields = $this->document($response->getContent());
            $ids = [];
            foreach ($fields->query('//input[@id]') as $input) {
                $ids[] = $input->getAttribute('id');
                $this->assertCount(1, $fields->query('//label[@for="'.$input->getAttribute('id').'"]'));
            }
            $this->assertSame($ids, array_values(array_unique($ids)));
        }
    }

    public function test_login_supports_password_managers_without_repopulating_password(): void
    {
        $response = $this->withSession(['_old_input' => ['email' => 'staff@example.com', 'password' => 'secret']])
            ->get(route('login'))->assertOk();
        $fields = $this->document($response->getContent());
        $password = $fields->query('//input[@name="password"]')->item(0);
        $this->assertSame('current-password', $password->getAttribute('autocomplete'));
        $this->assertFalse($password->hasAttribute('value'));
        $this->assertSame('staff@example.com', $fields->query('//input[@name="email"]')->item(0)
            ->getAttribute('value'));
    }

    public function test_pagination_keeps_search_filters_on_the_next_page(): void
    {
        Post::factory()->count(21)->create(['title' => 'Xe điện VinFast']);
        $response = $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.posts.index', ['q' => 'VinFast']))->assertOk();
        $document = $this->document($response->getContent());
        $next = $document->query('//nav[@aria-label="Phân trang"]//a[@rel="next"]');
        $this->assertCount(1, $next);
        $this->assertStringContainsString('q=VinFast', $next->item(0)->getAttribute('href'));
        $this->assertStringContainsString('page=2', $next->item(0)->getAttribute('href'));
        $this->get($next->item(0)->getAttribute('href'))->assertOk()->assertSee('Hiển thị 21–21');
    }

    public function test_pagination_recovers_from_out_of_range_pages_with_bounded_links(): void
    {
        Post::factory()->create(['title' => 'Xe điện VinFast']);
        $response = $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.posts.index', ['q' => 'VinFast', 'page' => 999]))
            ->assertOk();
        $document = $this->document($response->getContent());
        $links = $document->query('//nav[@aria-label="Phân trang"]//a');
        $this->assertCount(2, $links);
        foreach ($links as $link) {
            parse_str(parse_url($link->getAttribute('href'), PHP_URL_QUERY), $query);
            $this->assertSame('1', $query['page']);
            $this->assertSame('VinFast', $query['q']);
        }
        $previous = $document->query('//nav[@aria-label="Phân trang"]//a[@rel="prev"]');
        $this->assertCount(1, $previous);
        $this->get($previous->item(0)->getAttribute('href'))
            ->assertOk()->assertSee('Xe điện VinFast')->assertSee('Hiển thị 1–1');
    }

    public function test_pagination_stays_visible_for_empty_and_single_page_lists(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        foreach ([0, 1] as $total) {
            if ($total === 1) {
                Post::factory()->create();
            }
            $response = $this->get(route('admin.posts.index'))->assertOk();
            $document = $this->document($response->getContent());
            $this->assertCount(1, $document->query('//nav[@aria-label="Phân trang"]'));
            $this->assertCount(0, $document->query('//nav[@aria-label="Phân trang"]//a'));
            $current = $document->query('//nav[@aria-label="Phân trang"]//span[@aria-current="page"]');
            $this->assertCount(1, $current);
            $this->assertSame('Trang 1', $current->item(0)->getAttribute('aria-label'));
            $this->assertCount(2, $document->query('//nav[@aria-label="Phân trang"]//span[@aria-disabled="true"]'));
            $this->assertCount(1, $document->query('//select[@name="per_page"]'));
            $response->assertSee('Hiển thị '.$total.'–'.$total);
        }
    }

    public function test_admin_tables_have_captions_and_empty_states(): void
    {
        $response = $this->actingAs(User::factory()->admin()->create())->get(route('admin.posts.index'))->assertOk();
        $table = $this->document($response->getContent());
        $this->assertCount(1, $table->query('//table/caption'));
        $response->assertSee('Chưa có bài viết phù hợp');

    }
}
