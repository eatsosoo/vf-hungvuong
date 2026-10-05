<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Lead;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AuditLoggingTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_create_update_and_delete_audits_identify_actor_and_subject(): void
    {
        $actor = User::factory()->admin()->create();
        $this->actingAs($actor);
        $vehicle = Vehicle::factory()->create(['name' => 'Tên đầu', 'is_active' => true]);
        $vehicle->update(['name' => 'Tên sau', 'is_active' => false]);
        $vehicle->delete();

        $logs = AuditLog::query()->where('subject_type', Vehicle::class)
            ->where('subject_id', $vehicle->id)->orderBy('id')->get();
        $this->assertSame(['created', 'updated', 'deleted'], $logs->pluck('action')->all());
        $this->assertSame([$actor->id], $logs->pluck('user_id')->unique()->values()->all());
        $this->assertSame($actor->id, $logs->first()->actor->id);
        $this->assertContains('name', $logs->first()->changed_fields);
        $this->assertEqualsCanonicalizing(['name', 'is_active'], $logs[1]->changed_fields);
    }

    public function test_account_audits_exclude_password_and_remember_token_fields_and_values(): void
    {
        $account = User::factory()->create(['password' => 'NeverLogPassword!123', 'remember_token' => 'private-token']);
        $account->update(['name' => 'Updated name', 'password' => 'ChangedPassword!123',
            'remember_token' => 'changed-token']);

        $logs = AuditLog::query()->where('subject_type', User::class)
            ->where('subject_id', $account->id)->orderBy('id')->get();
        $this->assertCount(2, $logs);
        $this->assertSame(['name'], $logs[1]->changed_fields);
        foreach ($logs as $log) {
            $this->assertNull($log->user_id);
            $this->assertNotContains('password', $log->changed_fields);
            $this->assertNotContains('remember_token', $log->changed_fields);
            $this->assertNotContains('updated_at', $log->changed_fields);
            $payload = json_encode($log->getAttributes(), JSON_THROW_ON_ERROR);
            $this->assertStringNotContainsString('NeverLogPassword', $payload);
            $this->assertStringNotContainsString('ChangedPassword', $payload);
            $this->assertStringNotContainsString('private-token', $payload);
            $this->assertStringNotContainsString('changed-token', $payload);
        }
    }

    public function test_lead_audit_records_field_names_without_copying_contact_data(): void
    {
        $lead = Lead::factory()->create([
            'name' => 'Private Customer', 'phone' => '0912345678',
            'email' => 'private@example.test', 'message' => 'Private consultation message',
        ]);
        $log = AuditLog::query()->where('subject_type', Lead::class)->where('subject_id', $lead->id)->sole();
        $this->assertContains('phone', $log->changed_fields);
        $this->assertContains('message', $log->changed_fields);
        $payload = DB::table('audit_logs')->where('id', $log->id)->value('changed_fields');
        foreach (['Private Customer', '0912345678', 'private@example.test', 'Private consultation message'] as $value) {
            $this->assertStringNotContainsString($value, $payload);
        }
    }

    public function test_failed_database_transaction_rolls_back_its_audit_records(): void
    {
        $baseline = AuditLog::query()->count();
        try {
            DB::transaction(function (): void {
                Vehicle::factory()->create(['slug' => 'rolled-back']);
                throw new \RuntimeException('Abort operation');
            });
            $this->fail('The operation should fail.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Abort operation', $exception->getMessage());
        }

        $this->assertDatabaseMissing('vehicles', ['slug' => 'rolled-back']);
        $this->assertDatabaseCount('audit_logs', $baseline);
    }
}
