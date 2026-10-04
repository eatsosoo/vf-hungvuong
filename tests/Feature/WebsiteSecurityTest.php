<?php

namespace Tests\Feature;

use App\Jobs\NotifyNewLead;
use App\Models\Lead;
use App\Models\Media;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleVariant;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class WebsiteSecurityTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guests_cannot_access_admin(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
    }

    public function test_inactive_accounts_cannot_login_or_access_admin(): void
    {
        $user = User::factory()->admin()->inactive()->create();
        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->actingAs($user)->get(route('admin.dashboard'))->assertForbidden();
    }

    public function test_login_uses_credentials_and_logout_ends_session(): void
    {
        $user = User::factory()->admin()->create();
        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->post(route('admin.logout'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_login_is_rate_limited(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('login.store'), ['email' => 'blocked@example.com', 'password' => 'bad']);
        }
        $this->post(route('login.store'),
            ['email' => 'blocked@example.com',
                'password' => 'bad'])->assertTooManyRequests();
    }

    /** @return array<string, array{string}> */
    public static function restrictedRoles(): array
    {
        return ['manager' => ['manager'], 'sales' => ['sales'], 'editor' => ['editor']];
    }

    #[DataProvider('restrictedRoles')]
    public function test_only_admin_can_change_settings_or_accounts(string $role): void
    {
        $user = User::factory()->create(['role' => $role]);
        $this->actingAs($user)->get(route('admin.users.index'))->assertForbidden();
        $this->get(route('admin.settings.edit'))->assertForbidden();
        $this->put(route('admin.settings.update'), ['site_name' => 'Hacked'])->assertForbidden();
    }

    public function test_sales_cannot_access_another_agents_lead_or_export(): void
    {
        $sales = User::factory()->sales()->create();
        $other = User::factory()->sales()->create();
        $lead = Lead::factory()->create(['assigned_to' => $other->id, 'name' => 'Khách riêng của người khác']);
        $this->actingAs($sales)->get(route('admin.leads.index'))->assertDontSee($lead->name);
        $this->get(route('admin.leads.edit', $lead))->assertNotFound();
        $this->put(route('admin.leads.update', $lead), ['status' => 'won'])->assertForbidden();
        $this->get(route('admin.reports.export'))->assertForbidden();
        $this->assertSame('new', $lead->fresh()->status->value);
    }

    public function test_sales_cannot_assign_leads_even_when_they_own_them(): void
    {
        $sales = User::factory()->sales()->create();
        $lead = Lead::factory()->create(['assigned_to' => $sales->id]);
        $this->actingAs($sales)->put(route('admin.leads.update', $lead),
            ['status' => 'contacted', 'assigned_to' => $sales->id])->assertSessionHasErrors('assigned_to');
        $this->assertSame('new', $lead->fresh()->status->value);
    }

    public function test_public_lead_is_encrypted_and_cannot_set_internal_fields(): void
    {
        Queue::fake([NotifyNewLead::class]);
        $vehicle = Vehicle::factory()->create();
        $this->post(route('leads.store'), ['type' => 'consultation', 'name' => 'Nguyễn An', 'phone' => '0901234567',
            'vehicle_id' => $vehicle->id, 'consent' => '1', 'status' => 'won', 'assigned_to' => 999])
            ->assertRedirect(route('contact'))->assertSessionHas('success');
        $lead = Lead::query()->sole();
        $this->assertSame('0901234567', $lead->phone);
        $this->assertSame('new', $lead->status->value);
        $this->assertNull($lead->assigned_to);
        $this->assertNotSame('0901234567', DB::table('leads')->value('phone'));
        Queue::assertPushed(NotifyNewLead::class, fn ($job) => $job->leadId === $lead->id);
    }

    public function test_lead_requires_consent_and_matching_active_vehicle_variant(): void
    {
        Queue::fake([NotifyNewLead::class]);
        $vehicle = Vehicle::factory()->create(['is_active' => false]);
        $variant = VehicleVariant::factory()->create();
        $this->post(route('leads.store'), ['type' => 'quote', 'name' => 'An', 'phone' => 'bad',
            'vehicle_id' => $vehicle->id, 'vehicle_variant_id' => $variant->id])
            ->assertSessionHasErrors(['phone', 'consent', 'vehicle_id', 'vehicle_variant_id']);
        $this->assertDatabaseCount('leads', 0);
        Queue::assertNothingPushed();
    }

    public function test_test_drive_requires_future_date_and_rejects_honeypot(): void
    {
        Queue::fake([NotifyNewLead::class]);
        $this->post(route('leads.store'), ['type' => 'test_drive', 'name' => 'An', 'phone' => '0901234567',
            'consent' => '1', 'preferred_at' => '2000-01-01', 'website' => 'spam'])
            ->assertSessionHasErrors(['preferred_at', 'website']);
        $this->assertDatabaseCount('leads', 0);
    }

    public function test_csrf_is_required_for_real_web_posts(): void
    {
        $this->app['env'] = 'local';
        $this->post(route('leads.store'), ['type' => 'consultation'])->assertStatus(419);
        $this->assertDatabaseCount('leads', 0);
    }

    public function test_security_headers_and_admin_cache_policy(): void
    {
        $this->get(route('login'))->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('X-Content-Type-Options', 'nosniff')->assertHeader('X-Robots-Tag', 'noindex, nofollow')
            ->assertHeader('Content-Security-Policy');
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->get(route('admin.dashboard'))->assertHeader('Cache-Control', 'no-store, private');
    }

    public function test_upload_rejects_svg_and_executable_files(): void
    {
        Storage::fake('local');
        $user = User::factory()->admin()->create();
        $this->actingAs($user)->post(route('admin.media.store'),
            ['image' => UploadedFile::fake()->createWithContent('attack.svg', '<svg onload="alert(1)"></svg>')])
            ->assertSessionHasErrors('image');
        $this->post(route('admin.media.store'),
            ['image' => UploadedFile::fake()->createWithContent('attack.php', '<?php echo 1;')])
            ->assertSessionHasErrors('image');
        $this->assertDatabaseCount('media', 0);
        Storage::disk('local')->assertDirectoryEmpty('images');
    }

    public function test_valid_image_is_reencoded_and_reusable(): void
    {
        Storage::fake('local');
        $user = User::factory()->editor()->create();
        $this->actingAs($user)->post(route('admin.media.store'),
            ['image' => UploadedFile::fake()->image('photo.png', 100, 80), 'alt' => 'Ảnh xe'])
            ->assertSessionHas('success');
        $media = Media::query()->sole();
        Storage::disk('local')->assertExists($media->path);
        $this->assertStringEndsWith('.webp', $media->path);
        $this->get($media->url())->assertHeader('Content-Type', 'image/webp');
    }

    public function test_lead_names_and_notes_are_escaped_in_admin(): void
    {
        $user = User::factory()->admin()->create();
        $lead = Lead::factory()->create(['name' => '<script>alert(1)</script>']);
        $lead->notes()->create(['user_id' => $user->id, 'body' => '<img src=x onerror=alert(2)>']);
        $this->actingAs($user)->get(route('admin.leads.edit', $lead))
            ->assertOk()->assertSee('&lt;script&gt;', false)->assertDontSee('<script>alert(1)</script>', false)
            ->assertDontSee('<img src=x onerror=alert(2)>', false);
    }
}
