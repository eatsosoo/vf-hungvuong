<?php

namespace Tests\Feature;

use App\Enums\LeadStatus;
use App\Jobs\NotifyNewLead;
use App\Models\Lead;
use App\Models\Promotion;
use App\Models\Setting;
use App\Models\User;
use App\Models\Vehicle;
use Database\Seeders\SampleLeadSeeder;
use Database\Seeders\SamplePromotionSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SampleDataSafetyTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_sample_leads_are_labelled_safe_and_related_to_available_catalog_and_staff(): void
    {
        $this->freezeTime();
        Notification::fake();
        Queue::fake([NotifyNewLead::class]);
        $vehicle = Vehicle::factory()->create();
        $variant = $vehicle->variants()->create(['name' => 'Test variant']);
        Vehicle::factory()->create(['is_active' => false]);
        $sales = User::factory()->sales()->create();
        User::factory()->manager()->inactive()->create();
        User::factory()->editor()->create();

        $this->seed(SampleLeadSeeder::class);

        $this->assertDatabaseCount('leads', 48);
        $this->assertSame(24, Lead::query()->where('type', 'test_drive')->count());
        $this->assertSame(12, Lead::query()->where('type', 'consultation')->count());
        $this->assertSame(12, Lead::query()->where('type', 'quote')->count());
        foreach (Lead::query()->with('notes')->get() as $lead) {
            $this->assertStringStartsWith('[Mẫu] ', $lead->name);
            $this->assertStringStartsWith('[Dữ liệu mẫu] ', $lead->message);
            $this->assertStringEndsWith('@example.test', $lead->email);
            $this->assertMatchesRegularExpression('/\A000000\d{4}\z/', $lead->phone);
            $this->assertStringStartsWith('demo:lead:', $lead->source);
            $this->assertSame($vehicle->id, $lead->vehicle_id);
            $this->assertSame($variant->id, $lead->vehicle_variant_id);
            $this->assertContains($lead->status, LeadStatus::forType($lead->type));
            $this->assertNotNull($lead->consented_at);
            $this->assertContains($lead->assigned_to, [null, $sales->id]);
            foreach ($lead->notes as $note) {
                $this->assertSame($sales->id, $note->user_id);
                $this->assertStringStartsWith('[Dữ liệu mẫu] ', $note->body);
            }
            if ($lead->type !== 'test_drive') {
                $this->assertNull($lead->appointment_at);
            }
        }
        Queue::assertNothingPushed();
        Notification::assertNothingSent();
    }

    public function test_repeat_sample_lead_seed_keeps_manual_changes_notes_and_real_leads(): void
    {
        Vehicle::factory()->create();
        $sales = User::factory()->sales()->create();
        $realLead = Lead::factory()->create(['source' => 'website']);
        $this->seed(SampleLeadSeeder::class);
        $sample = Lead::query()->where('source', 'demo:lead:001')->sole();
        $sample->update([
            'name' => 'Manually edited', 'message' => 'Edited message', 'status' => LeadStatus::Contacted,
        ]);
        $sample->notes()->create(['user_id' => $sales->id, 'body' => 'Manual note']);
        $before = $sample->fresh()->getAttributes();
        $noteCount = $sample->notes()->count();
        $realBefore = $realLead->fresh()->getAttributes();

        $this->seed(SampleLeadSeeder::class);

        $this->assertDatabaseCount('leads', 49);
        $this->assertSame($before, $sample->fresh()->getAttributes());
        $this->assertSame($noteCount, $sample->notes()->count());
        $this->assertSame($realBefore, $realLead->fresh()->getAttributes());
    }

    public function test_sample_leads_require_an_active_vehicle_but_not_staff_or_variants(): void
    {
        Vehicle::factory()->create(['is_active' => false]);
        $this->seed(SampleLeadSeeder::class);
        $this->assertDatabaseCount('leads', 0);

        Vehicle::factory()->create();
        $this->seed(SampleLeadSeeder::class);
        $this->assertDatabaseCount('leads', 48);
        $this->assertSame(48, Lead::query()->whereNull('assigned_to')->whereNull('vehicle_variant_id')->count());
        $this->assertDatabaseCount('lead_notes', 0);
        $this->assertDatabaseCount('users', 0);
    }

    public function test_sample_leads_and_promotions_are_never_created_in_production(): void
    {
        Vehicle::factory()->create(['slug' => 'vf5']);
        $this->app->instance('env', 'production');
        $this->artisan('db:seed', ['--class' => SampleLeadSeeder::class, '--force' => true])->assertSuccessful();
        $this->artisan('db:seed', ['--class' => SamplePromotionSeeder::class, '--force' => true])->assertSuccessful();

        $this->assertDatabaseCount('leads', 0);
        $this->assertDatabaseCount('promotions', 0);
        $this->assertDatabaseCount('users', 0);
    }

    public function test_promotion_seed_skips_missing_models_and_preserves_edited_promotions(): void
    {
        $this->freezeTime();
        $vehicle = Vehicle::factory()->create(['slug' => 'vf5']);
        Vehicle::factory()->create(['slug' => 'vf6', 'is_active' => false]);
        $this->seed(SamplePromotionSeeder::class);
        $this->assertDatabaseCount('promotions', 4);
        foreach (Promotion::query()->with('vehicles')->get() as $promotion) {
            $this->assertSame([$vehicle->id], $promotion->vehicles->modelKeys());
            $this->assertStringContainsString('không phải chính sách bán hàng chính thức', $promotion->body);
            $this->assertStringContainsString(route('contact', ['type' => 'consultation'], false), $promotion->body);
            $this->assertLessThanOrEqual(now(), $promotion->starts_at);
            $this->assertGreaterThan(now(), $promotion->ends_at);
        }
        $promotion = Promotion::query()->firstOrFail();
        $promotion->update(['title' => 'Edited offer', 'body' => 'Manual copy', 'is_active' => false]);
        $promotion->vehicles()->detach();
        $before = $promotion->fresh()->getAttributes();

        $this->seed(SamplePromotionSeeder::class);

        $this->assertDatabaseCount('promotions', 4);
        $this->assertSame($before, $promotion->fresh()->getAttributes());
        $this->assertSame([], $promotion->fresh()->vehicles->modelKeys());
    }

    public function test_setting_seed_preserves_custom_values_and_reads_later_updates_immediately(): void
    {
        $siteName = Setting::factory()->create(['key' => 'site_name', 'value' => 'Custom dealer']);
        Setting::factory()->create(['key' => 'notify_leads', 'value' => '0']);
        $this->seed(SettingSeeder::class);
        $this->seed(SettingSeeder::class);

        $this->assertDatabaseCount('settings', 5);
        $this->assertSame('Custom dealer', Setting::values()['site_name']);
        $this->assertSame('0', Setting::values()['notify_leads']);
        $siteName->update(['value' => 'Updated dealer']);

        $this->assertSame('Updated dealer', Setting::values()['site_name']);
    }
}
