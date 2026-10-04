<?php

namespace App\Jobs;

use App\Models\Lead;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\NewLeadNotification;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class NotifyNewLead implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $uniqueFor = 3600;

    public function uniqueId(): string
    {
        return 'lead-'.$this->leadId;
    }

    public function __construct(public int $leadId) {}

    public function handle(): void
    {
        if (Setting::query()->where('key', 'notify_leads')->value('value') !== '1') {
            return;
        }
        $lead = Lead::query()->find($this->leadId);
        if (! $lead) {
            return;
        }
        User::query()->where('is_active', true)->whereIn('role', ['admin', 'manager'])->each(
            function (User $user) use ($lead): void {
                $sent = $user->notifications()->where('type', NewLeadNotification::class)
                    ->where('data->lead_id', $lead->id)->exists();
                if (! $sent) {
                    $user->notify(new NewLeadNotification($lead->id, $lead->type));
                }
            }
        );
    }
}
