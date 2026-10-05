<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ReportExportTest extends TestCase
{
    use LazilyRefreshDatabase;

    #[DataProvider('reportRoles')]
    public function test_authorized_export_round_trips_encrypted_fields_and_records_one_audit(string $role): void
    {
        $user = User::factory()->create(['role' => $role]);
        $first = Lead::factory()->create([
            'name' => 'Nguyễn, "An"', 'phone' => '0901234567', 'email' => 'an@example.test',
            'source' => 'website', 'status' => 'contacted',
        ]);
        $second = Lead::factory()->create(['name' => 'Khách hàng B', 'type' => 'quote', 'status' => 'won']);
        $response = $this->actingAs($user)->get(route('admin.reports.export'))->assertOk()
            ->assertDownload('khach-hang.csv')->assertHeader('Cache-Control', 'no-store, private');
        $content = $response->streamedContent();

        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, substr($content, 3));
        rewind($stream);
        $this->assertSame(['ID', 'Họ tên', 'Điện thoại', 'Email', 'Loại', 'Trạng thái', 'Nguồn'],
            fgetcsv($stream, escape: ''));
        $this->assertSame([(string) $first->id, $first->name, $first->phone, $first->email,
            'consultation', 'contacted', 'website'], fgetcsv($stream, escape: ''));
        $this->assertSame([(string) $second->id, $second->name, $second->phone, '',
            'quote', 'won', 'website'], fgetcsv($stream, escape: ''));
        $this->assertFalse(fgetcsv($stream, escape: ''));
        fclose($stream);
        $export = AuditLog::query()->where('action', 'export')->sole();
        $this->assertSame($user->id, $export->user_id);
        $this->assertSame(Lead::class, $export->subject_type);
        $this->assertSame([], $export->changed_fields);
    }

    public function test_unauthorized_export_does_not_create_an_export_audit_record(): void
    {
        $url = route('admin.reports.export');
        $this->get($url)->assertRedirect(route('login'));
        foreach (['sales', 'editor'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]))->get($url)->assertForbidden();
        }
        $this->assertSame(0, AuditLog::query()->where('action', 'export')->count());
    }

    public function test_export_rate_limit_blocks_the_sixth_request_without_creating_another_audit(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        foreach (range(1, 5) as $request) {
            $this->get(route('admin.reports.export'))->assertOk();
        }
        $this->get(route('admin.reports.export'))->assertTooManyRequests();
        $this->assertSame(5, AuditLog::query()->where('action', 'export')->count());
    }

    /** @return array<string, array{string}> */
    public static function reportRoles(): array
    {
        return ['admin' => ['admin'], 'manager' => ['manager']];
    }
}
