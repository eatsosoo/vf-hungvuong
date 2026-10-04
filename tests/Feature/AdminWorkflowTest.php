<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\Page;
use App\Models\Post;
use App\Models\Promotion;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminWorkflowTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_screens_render_with_records(): void
    {
        $user = User::factory()->admin()->create();
        $vehicle = Vehicle::factory()->create();
        $promotion = Promotion::factory()->create();
        $page = Page::factory()->create();
        $lead = Lead::factory()->create();
        $post = Post::factory()->create();
        $this->actingAs($user);
        foreach (['admin.dashboard',
            'admin.vehicles.index',
            'admin.vehicles.create',
            'admin.posts.index',
            'admin.posts.create',
            'admin.pages.index',
            'admin.pages.create',
            'admin.promotions.index',
            'admin.promotions.create',
            'admin.users.index',
            'admin.users.create',
            'admin.settings.edit',
            'admin.media.index',
            'admin.leads.index',
            'admin.reports.index',
            'admin.audit.index'] as $route) {
            $this->get(route($route))->assertOk();
        }
        foreach (['vehicles' => $vehicle, 'posts' => $post, 'pages' => $page, 'promotions' => $promotion,
            'leads' => $lead, 'users' => $user] as $resource => $record) {
            $this->get(route('admin.'.$resource.'.edit', $record))->assertOk();
        }
        $this->get(route('admin.taxonomies.index', 'categories'))->assertOk();
        $this->get(route('admin.taxonomies.index', 'tags'))->assertOk();
    }

    public function test_public_vehicle_promotion_contact_and_page_render(): void
    {
        $vehicle = Vehicle::factory()->create();
        $promotion = Promotion::factory()->create();
        $page = Page::factory()->create();
        $this->get(route('home'))->assertOk()->assertSee($vehicle->name);
        $this->get(route('vehicles.index'))->assertOk()->assertSee($vehicle->name);
        $this->get(route('vehicles.show', $vehicle->slug))->assertOk()->assertSee($vehicle->name);
        $this->get(route('promotions.index'))->assertOk()->assertSee($promotion->title);
        $this->get(route('promotions.show', $promotion->slug))->assertOk()->assertSee($promotion->title);
        $this->get(route('pages.show', $page->slug))->assertOk()->assertSee($page->title);
        $this->get(route('contact'))->assertOk()->assertSee('Gửi yêu cầu');
    }

    public function test_vehicle_variants_and_colors_are_saved_transactionally(): void
    {
        $user = User::factory()->admin()->create();
        $this->actingAs($user)->post(route('admin.vehicles.store'),
            ['name' => 'VF 7',
                'slug' => 'vf7',
                'is_active' => '1',
                'specifications' => '{"Số chỗ":5}', 'variants' => [['name' => 'Eco', 'price' => '700000000']],
                'colors' => [['name' => 'Trắng', 'hex' => '#ffffff']]])->assertRedirect();
        $vehicle = Vehicle::query()->sole();
        $this->assertSame(['Số chỗ' => 5], $vehicle->specifications);
        $this->assertDatabaseHas('vehicle_variants', ['vehicle_id' => $vehicle->id, 'name' => 'Eco']);
        $this->assertDatabaseHas('vehicle_colors', ['vehicle_id' => $vehicle->id, 'name' => 'Trắng']);
    }

    public function test_manager_assigns_lead_and_sales_records_quote_and_notes(): void
    {
        $manager = User::factory()->manager()->create();
        $sales = User::factory()->sales()->create();
        $lead = Lead::factory()->create(['type' => 'quote']);
        $this->actingAs($manager)->put(route('admin.leads.update',
            $lead),
            ['status' => 'contacted',
                'assigned_to' => $sales->id])
            ->assertSessionHas('success');
        $this->actingAs($sales)->put(route('admin.leads.update',
            $lead),
            ['status' => 'won',
                'quote_amount' => 700000000,
                'quote_details' => 'Báo giá đã xác nhận', 'note' => 'Khách đồng ý'])->assertSessionHas('success');
        $this->assertSame($sales->id, $lead->fresh()->assigned_to);
        $this->assertSame('won', $lead->fresh()->status->value);
        $this->assertSame('Khách đồng ý', $lead->notes()->sole()->body);
    }

    public function test_last_active_admin_cannot_be_disabled(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->put(route('admin.users.update',
            $admin),
            ['name' => $admin->name,
                'email' => $admin->email,
                'role' => 'editor', 'is_active' => '0'])->assertSessionHasErrors('role');
        $this->assertTrue($admin->fresh()->is_active);
        $this->assertSame('admin', $admin->fresh()->role->value);
    }

    public function test_admin_creates_account_with_hashed_password(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->post(route('admin.users.store'), ['name' => 'Sales', 'email' => 'sales@example.com',
            'role' => 'sales',
            'is_active' => '1',
            'password' => 'StrongPassword!123',
            'password_confirmation' => 'StrongPassword!123'])
            ->assertRedirect();
        $account = User::query()->where('email', 'sales@example.com')->sole();
        $this->assertTrue(Hash::check('StrongPassword!123', $account->password));
        $this->assertSame('sales', $account->role->value);
        $this->assertDatabaseMissing('audit_logs', ['changed_fields' => '["password"]']);
    }

    public function test_settings_reject_unsafe_urls_and_ignore_unknown_keys(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->put(route('admin.settings.update'),
            ['site_name' => 'Studio',
                'notify_leads' => '1',
                'zalo_url' => 'javascript:alert(1)'])->assertSessionHasErrors('zalo_url');
        $this->put(route('admin.settings.update'),
            ['site_name' => 'Studio',
                'notify_leads' => '1',
                'app_key' => 'hacked'])
            ->assertSessionHas('success');
        $this->assertDatabaseHas('settings', ['key' => 'site_name', 'value' => 'Studio']);
        $this->assertDatabaseMissing('settings', ['key' => 'app_key']);
    }

    public function test_export_neutralizes_spreadsheet_formula_injection(): void
    {
        $admin = User::factory()->admin()->create();
        Lead::factory()->create(['name' => '=HYPERLINK("https://evil.example")']);
        $response = $this->actingAs($admin)->get(route('admin.reports.export'));
        $response->assertDownload('khach-hang.csv');
        $this->assertStringContainsString("'=HYPERLINK", $response->streamedContent());
        $this->assertDatabaseHas('audit_logs', ['action' => 'export', 'user_id' => $admin->id]);
    }
}
