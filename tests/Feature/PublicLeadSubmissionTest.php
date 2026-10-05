<?php

namespace Tests\Feature;

use App\Jobs\NotifyNewLead;
use App\Models\Lead;
use App\Models\Vehicle;
use App\Models\VehicleVariant;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PublicLeadSubmissionTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->freezeTime();
        Queue::fake([NotifyNewLead::class]);
    }

    #[DataProvider('submissionLanguages')]
    public function test_json_submission_saves_vehicle_context_and_returns_only_localized_confirmation(
        string $prefix,
        string $locale,
        string $message,
    ): void {
        $vehicle = Vehicle::factory()->create();
        $variant = VehicleVariant::factory()->create(['vehicle_id' => $vehicle->id]);
        $preferredAt = now()->addDay()->format('Y-m-d\TH:i');
        $data = $this->validLeadData() + [
            'vehicle_id' => $vehicle->id,
            'vehicle_variant_id' => $variant->id,
            'email' => 'visitor@example.test',
            'message' => 'Tôi muốn trải nghiệm mẫu xe này.',
            'source' => 'vehicle-detail',
            'preferred_at' => $preferredAt,
        ];
        $data['type'] = 'test_drive';

        $this->postJson(route($prefix.'leads.store'), $data)->assertCreated()
            ->assertHeader('Content-Language', $locale)->assertExactJson(['message' => $message]);
        $lead = Lead::query()->sole();
        $this->assertSame($vehicle->id, $lead->vehicle_id);
        $this->assertSame($variant->id, $lead->vehicle_variant_id);
        $this->assertSame('vehicle-detail', $lead->source);
        $this->assertSame($preferredAt, $lead->preferred_at->format('Y-m-d\TH:i'));
        $this->assertSame(now()->toDateTimeString(), $lead->consented_at->toDateTimeString());
        foreach (['phone', 'email', 'message'] as $field) {
            $this->assertSame($data[$field], $lead->{$field});
            $this->assertNotSame($data[$field], DB::table('leads')->value($field));
        }
        Queue::assertPushed(NotifyNewLead::class, fn (NotifyNewLead $job): bool => $job->leadId === $lead->id);
    }

    /** @return array<string, array{string, string, string}> */
    public static function submissionLanguages(): array
    {
        return [
            'vietnamese' => ['', 'vi', 'Đã gửi yêu cầu. Đại lý sẽ liên hệ với bạn.'],
            'english' => ['en.', 'en', 'Your request has been sent. Our team will contact you.'],
        ];
    }

    public function test_english_html_submission_redirects_to_english_contact_and_defaults_the_source(): void
    {
        $this->post(route('en.leads.store'), $this->validLeadData())->assertRedirect(route('en.contact'))
            ->assertSessionHas('success', 'Your request has been sent. Our team will contact you.');
        $lead = Lead::query()->sole();
        $this->assertSame('website', $lead->source);
        $this->assertNull($lead->preferred_at);
        $this->assertNull($lead->vehicle_id);
        $this->assertNull($lead->vehicle_variant_id);
    }

    public function test_variant_from_another_active_vehicle_is_rejected_without_saving_or_dispatching(): void
    {
        $vehicle = Vehicle::factory()->create();
        $otherVariant = VehicleVariant::factory()->create();

        $this->postJson(route('leads.store'), $this->validLeadData() + [
            'vehicle_id' => $vehicle->id, 'vehicle_variant_id' => $otherVariant->id,
        ])->assertUnprocessable()->assertJsonValidationErrors('vehicle_variant_id');
        $this->assertDatabaseCount('leads', 0);
        Queue::assertNothingPushed();
    }

    #[DataProvider('invalidTestDriveTimes')]
    public function test_test_drive_requires_a_time_strictly_after_now(string $time): void
    {
        $data = $this->validLeadData();
        $data['type'] = 'test_drive';
        if ($time === 'now') {
            $data['preferred_at'] = now()->toDateTimeString();
        }

        $this->postJson(route('en.leads.store'), $data)->assertUnprocessable()
            ->assertJsonValidationErrors('preferred_at')
            ->assertJsonPath('errors.preferred_at.0', $time === 'now' ? 'Please choose a future time.'
                : 'Please choose a test drive date and time.');
        $this->assertDatabaseCount('leads', 0);
        Queue::assertNothingPushed();
    }

    /** @return array<string, array{string}> */
    public static function invalidTestDriveTimes(): array
    {
        return ['missing time' => ['missing'], 'exact current time' => ['now']];
    }

    /** @param array<string, mixed> $invalidData */
    #[DataProvider('invalidPayloads')]
    public function test_json_validation_rejects_malformed_or_oversized_fields(
        array $invalidData,
        string $field,
    ): void {
        $this->postJson(route('leads.store'), array_replace($this->validLeadData(), $invalidData))
            ->assertUnprocessable()->assertJsonValidationErrors($field);
        $this->assertDatabaseCount('leads', 0);
        Queue::assertNothingPushed();
    }

    /** @return array<string, array{array<string, mixed>, string}> */
    public static function invalidPayloads(): array
    {
        return [
            'invalid type' => [['type' => 'purchase'], 'type'],
            'array name' => [['name' => ['An']], 'name'],
            'long name' => [['name' => str_repeat('a', 101)], 'name'],
            'invalid email' => [['email' => 'not-an-email'], 'email'],
            'long message' => [['message' => str_repeat('a', 2001)], 'message'],
            'invalid source' => [['source' => 'https://example.test/path'], 'source'],
            'long source' => [['source' => str_repeat('a', 101)], 'source'],
        ];
    }

    public function test_json_validation_translates_field_names_and_keeps_failed_submission_private(): void
    {
        $this->postJson(route('en.leads.store'), ['type' => 'consultation'])
            ->assertUnprocessable()->assertHeader('Content-Language', 'en')
            ->assertJsonPath('errors.name.0', 'Please enter Full name.')
            ->assertJsonPath('errors.phone.0', 'Please enter Phone number.')
            ->assertJsonPath('errors.consent.0', 'Please agree to be contacted by the dealership.');
        $this->assertDatabaseCount('leads', 0);
        Queue::assertNothingPushed();
    }

    /** @return array{type: string, name: string, phone: string, consent: string} */
    private function validLeadData(): array
    {
        return ['type' => 'consultation', 'name' => 'Nguyễn An', 'phone' => '+84 (90) 123-4567', 'consent' => '1'];
    }
}
