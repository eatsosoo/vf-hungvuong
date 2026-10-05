<?php

namespace Tests\Feature;

use App\Jobs\NotifyNewLead;
use App\Models\Lead;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\NewLeadNotification;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class LeadNotificationRecipientsTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_only_active_admins_and_managers_receive_new_lead_notification(): void
    {
        Notification::fake();
        $admin = User::factory()->admin()->create();
        $manager = User::factory()->manager()->create();
        $excluded = collect([
            User::factory()->admin()->inactive()->create(),
            User::factory()->manager()->inactive()->create(),
            User::factory()->sales()->create(),
            User::factory()->editor()->create(),
        ]);
        $lead = Lead::factory()->create(['type' => 'quote']);
        Setting::factory()->create(['key' => 'notify_leads', 'value' => '1']);

        (new NotifyNewLead($lead->id))->handle();

        Notification::assertSentTo([$admin, $manager], NewLeadNotification::class,
            function (NewLeadNotification $notification, array $channels) use ($admin, $lead): bool {
                return $channels === ['database'] && $notification->toArray($admin) === [
                    'lead_id' => $lead->id, 'type' => 'quote',
                ];
            });
        Notification::assertNotSentTo($excluded, NewLeadNotification::class);
        Notification::assertCount(2);
    }

    public function test_absent_notification_setting_or_deleted_lead_sends_nothing(): void
    {
        Notification::fake();
        User::factory()->admin()->create();
        $lead = Lead::factory()->create();
        (new NotifyNewLead($lead->id))->handle();
        Notification::assertNothingSent();

        Setting::factory()->create(['key' => 'notify_leads', 'value' => '1']);
        $leadId = $lead->id;
        $lead->delete();
        (new NotifyNewLead($leadId))->handle();
        Notification::assertNothingSent();
    }

    public function test_retry_delivers_to_remaining_manager_without_duplicating_prior_admin_delivery(): void
    {
        $admin = User::factory()->admin()->create();
        $manager = User::factory()->manager()->create();
        $lead = Lead::factory()->create();
        $otherLead = Lead::factory()->create();
        Setting::factory()->create(['key' => 'notify_leads', 'value' => '1']);
        $admin->notify(new NewLeadNotification($lead->id, $lead->type));
        $manager->notify(new NewLeadNotification($otherLead->id, $otherLead->type));

        $job = new NotifyNewLead($lead->id);
        $job->handle();
        $job->handle();

        $this->assertSame(1, $admin->notifications()->count());
        $this->assertSame(2, $manager->notifications()->count());
        $this->assertSame(1, $manager->notifications()->where('data->lead_id', $lead->id)->count());
        $this->assertSame(['lead_id' => $lead->id, 'type' => 'consultation'], $admin->notifications()->sole()->data);
    }
}
