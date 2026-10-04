<?php

namespace App\Actions\Leads;

use App\Jobs\NotifyNewLead;
use App\Models\Lead;
use Illuminate\Support\Facades\DB;

class ReceiveLead
{
    /** @param array<string, mixed> $data */
    public function handle(array $data): Lead
    {
        unset($data['consent'], $data['website']);
        $data['source'] = $data['source'] ?? 'website';

        return DB::transaction(function () use ($data): Lead {
            $lead = Lead::query()->create([...$data, 'status' => 'new', 'consented_at' => now()]);
            NotifyNewLead::dispatch($lead->id)->afterCommit();

            return $lead;
        });
    }
}
