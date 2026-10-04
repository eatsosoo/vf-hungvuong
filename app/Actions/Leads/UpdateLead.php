<?php

namespace App\Actions\Leads;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class UpdateLead
{
    /** @param array<string, mixed> $data */
    public function handle(User $user, Lead $lead, array $data): void
    {
        DB::transaction(function () use ($user, $data, $lead): void {
            $lead = Lead::query()->lockForUpdate()->findOrFail($lead->id);
            Gate::forUser($user)->authorize('update', $lead);
            $note = $data['note'] ?? null;
            unset($data['note']);
            if (in_array($lead->status->value, ['won', 'lost', 'completed', 'cancelled'], true) &&
                $data['status'] !== $lead->status->value && ! $user->can('assign', Lead::class)) {
                abort(403, 'Chỉ quản lý có thể mở lại yêu cầu đã kết thúc.');
            }
            $lead->update($data);
            if ($note) {
                $lead->notes()->create(['body' => $note, 'user_id' => $user->id]);
            }
        });
    }
}
