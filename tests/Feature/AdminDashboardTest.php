<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\Post;
use App\Models\User;
use App\Notifications\NewLeadNotification;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_dashboard_counts_use_the_correct_types_statuses_and_sales_assignment(): void
    {
        $sales = User::factory()->sales()->create();
        $manager = User::factory()->manager()->create();
        foreach ([
            ['consultation', 'new'], ['quote', 'new'], ['quote', 'contacted'], ['quote', 'won'],
            ['test_drive', 'new'], ['test_drive', 'confirmed'], ['test_drive', 'completed'],
        ] as [$type, $status]) {
            Lead::factory()->create(['assigned_to' => $sales->id, 'type' => $type, 'status' => $status]);
        }
        Lead::factory()->create(['type' => 'quote', 'assigned_to' => $manager->id]);
        Lead::factory()->create(['type' => 'test_drive', 'assigned_to' => null]);
        Post::factory()->count(2)->create();
        Post::factory()->published()->create();

        $this->actingAs($sales)->get(route('admin.dashboard'))->assertOk()
            ->assertViewHas('newLeads', 3)->assertViewHas('quotes', 2)
            ->assertViewHas('appointments', 2)->assertViewHas('drafts', null);
        $this->actingAs($manager)->get(route('admin.dashboard'))->assertOk()
            ->assertViewHas('newLeads', 5)->assertViewHas('quotes', 3)
            ->assertViewHas('appointments', 3)->assertViewHas('drafts', 2);
    }

    public function test_editor_dashboard_counts_only_own_drafts_and_omits_lead_statistics(): void
    {
        $editor = User::factory()->editor()->create();
        Post::factory()->count(2)->create(['user_id' => $editor->id]);
        Post::factory()->published()->create(['user_id' => $editor->id]);
        Post::factory()->scheduled()->create(['user_id' => $editor->id]);
        Post::factory()->create();
        Lead::factory()->create();

        $this->actingAs($editor)->get(route('admin.dashboard'))->assertOk()
            ->assertViewHas('drafts', 2)->assertViewHas('newLeads', null)
            ->assertViewHas('quotes', null)->assertViewHas('appointments', null);
    }

    public function test_dashboard_lists_only_ten_most_recent_own_unread_notifications(): void
    {
        $this->freezeTime();
        $user = User::factory()->admin()->create();
        $newest = null;
        $oldest = null;
        foreach (range(0, 10) as $index) {
            $notification = $this->notification($user, $index + 1);
            $notification->update(['created_at' => now()->subMinutes($index)]);
            $newest ??= $notification;
            $oldest = $notification;
        }
        $alreadyRead = $this->notification($user, 12);
        $alreadyRead->markAsRead();
        $other = $this->notification(User::factory()->admin()->create(), 13);

        $response = $this->actingAs($user)->get(route('admin.dashboard'))->assertOk();
        $visible = $response->viewData('notifications');
        $this->assertCount(10, $visible);
        $this->assertSame($newest->id, $visible->first()->id);
        $this->assertFalse($visible->contains('id', $oldest->id));
        $this->assertFalse($visible->contains('id', $alreadyRead->id));
        $this->assertFalse($visible->contains('id', $other->id));
    }

    public function test_marking_notifications_read_affects_only_current_users_unread_records(): void
    {
        $this->freezeSecond();
        $user = User::factory()->sales()->create();
        $own = $this->notification($user, 1);
        $read = $this->notification($user, 2);
        $read->update(['read_at' => now()->subDay()]);
        $other = $this->notification(User::factory()->sales()->create(), 3);

        $this->actingAs($user)->from(route('admin.dashboard'))->post(route('admin.notifications.read'), [
            'notification_id' => $other->id,
        ])->assertRedirect(route('admin.dashboard'))->assertSessionHas('success');
        $this->assertTrue($own->fresh()->read_at->equalTo(now()));
        $this->assertTrue($read->fresh()->read_at->equalTo(now()->subDay()));
        $this->assertNull($other->fresh()->read_at);
        $this->assertSame(0, $user->unreadNotifications()->count());
    }

    private function notification(User $user, int $leadId): DatabaseNotification
    {
        return $user->notifications()->create([
            'id' => (string) Str::uuid(), 'type' => NewLeadNotification::class,
            'data' => ['lead_id' => $leadId, 'type' => 'consultation'],
        ]);
    }
}
