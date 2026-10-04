<?php

namespace Tests\Feature;

use App\Models\User;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class DatePickerTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->withViewErrors([]);
    }

    public function test_date_picker_keeps_native_date_constraints_and_accessible_calendar_controls(): void
    {
        $view = $this->blade(<<<'BLADE'
            <x-date-picker name="from" label="Từ ngày" value="2026-10-03" required
                min="2026-01-01" max="2026-12-31" step="1" hint="Chọn ngày bắt đầu." />
            BLADE);
        $document = $this->document((string) $view);
        $input = $this->element($document, '//input[@name="from"]');

        $this->assertSame('date', $input->getAttribute('type'));
        $this->assertSame('2026-10-03', $input->getAttribute('value'));
        $this->assertSame('2026-01-01', $input->getAttribute('min'));
        $this->assertSame('2026-12-31', $input->getAttribute('max'));
        $this->assertSame('1', $input->getAttribute('step'));
        $this->assertTrue($input->hasAttribute('required'));
        $this->assertTrue($input->hasAttribute('data-date-picker-input'));
        $this->assertSame('false', $input->getAttribute('aria-invalid'));
        $this->assertSame('field-from-hint', $input->getAttribute('aria-describedby'));
        $this->assertCount(1, $document->query('//label[@for="field-from"]'));
        $this->assertSame('Chọn ngày bắt đầu.',
            $this->element($document, '//*[@id="field-from-hint"]')->textContent);

        $toggle = $this->element($document, '//button[@data-date-picker-toggle]');
        $this->assertSame('button', $toggle->getAttribute('type'));
        $this->assertTrue($toggle->hasAttribute('hidden'));
        $this->assertNotSame('', $toggle->getAttribute('aria-label'));
        $calendar = $this->element($document, '//dialog[@id="field-from-calendar"]');
        $this->assertSame('field-from-calendar-title', $calendar->getAttribute('aria-labelledby'));
        $this->assertCount(1, $document->query('//*[@id="field-from-calendar-title"]'));
    }

    public function test_datetime_field_retains_nested_old_input_and_links_its_validation_error(): void
    {
        $this->withSession(['_old_input' => [
            'appointments' => [['starts_at' => '2026-11-05T14:45']],
        ]]);
        $this->app['request']->setLaravelSession($this->app['session']->driver());
        $this->withViewErrors(['appointments.0.starts_at' => 'Vui lòng chọn thời gian hợp lệ.']);
        $view = $this->blade(<<<'BLADE'
            <x-field name="appointments[0][starts_at]" label="Lịch hẹn" type="datetime-local"
                value="2026-10-03T08:30" hint="Giờ Việt Nam." />
            BLADE);
        $document = $this->document((string) $view);
        $input = $this->element($document, '//input[@name="appointments[0][starts_at]"]');

        $this->assertSame('datetime-local', $input->getAttribute('type'));
        $this->assertSame('2026-11-05T14:45', $input->getAttribute('value'));
        $this->assertSame('field-appointments-0-starts_at', $input->getAttribute('id'));
        $this->assertSame('true', $input->getAttribute('aria-invalid'));
        $this->assertSame('field-appointments-0-starts_at-hint field-appointments-0-starts_at-error',
            $input->getAttribute('aria-describedby'));
        $this->assertCount(1, $document->query('//label[@for="field-appointments-0-starts_at"]'));
        $this->assertSame('Vui lòng chọn thời gian hợp lệ.',
            $this->element($document, '//*[@id="field-appointments-0-starts_at-error"]')->textContent);
        $this->assertCount(1, $document->query('//*[@data-date-picker]'));
    }

    public function test_custom_ids_readonly_disabled_and_old_input_opt_out_are_preserved(): void
    {
        $this->withSession(['_old_input' => ['appointment_at' => '2026-11-05T14:45']]);
        $this->app['request']->setLaravelSession($this->app['session']->driver());
        $view = $this->blade(<<<'BLADE'
            <x-field name="appointment_at" label="Lịch xác nhận" type="datetime-local"
                value="2026-10-03T08:30" :use-old="false" id="confirmed-appointment"
                readonly disabled data-context="confirmation" />
            BLADE);
        $document = $this->document((string) $view);
        $input = $this->element($document, '//input[@name="appointment_at"]');

        $this->assertSame('2026-10-03T08:30', $input->getAttribute('value'));
        $this->assertSame('confirmed-appointment', $input->getAttribute('id'));
        $this->assertTrue($input->hasAttribute('readonly'));
        $this->assertTrue($input->hasAttribute('disabled'));
        $this->assertSame('confirmation', $input->getAttribute('data-context'));
        $this->assertCount(1, $document->query('//label[@for="confirmed-appointment"]'));
        $this->assertCount(1, $document->query('//dialog[@id="confirmed-appointment-calendar"]'));
    }

    public function test_contact_uses_shared_datetime_picker_and_preserves_customer_proposal(): void
    {
        $response = $this->withSession(['_old_input' => [
            'type' => 'test_drive', 'preferred_at' => '2026-11-05T14:45',
        ]])->get(route('contact', ['type' => 'test_drive']))->assertOk();
        $document = $this->document($response->getContent());
        $input = $this->element($document, '//*[@id="appointmentField"]//input[@name="preferred_at"]');

        $this->assertSame('datetime-local', $input->getAttribute('type'));
        $this->assertSame('2026-11-05T14:45', $input->getAttribute('value'));
        $this->assertTrue($input->hasAttribute('data-date-picker-input'));
        $this->assertCount(1, $document->query('//*[@id="appointmentField"]//*[@data-date-picker]'));
        $this->assertCount(1, $document->query('//label[@for="'.$input->getAttribute('id').'"]'));
    }

    public function test_report_filters_use_shared_date_pickers_and_preserve_query_dates(): void
    {
        $response = $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.reports.index', ['from' => '2026-10-01', 'to' => '2026-10-31']))->assertOk();
        $document = $this->document($response->getContent());

        foreach (['from' => '2026-10-01', 'to' => '2026-10-31'] as $name => $value) {
            $input = $this->element($document, '//input[@name="'.$name.'"]');
            $this->assertSame('date', $input->getAttribute('type'));
            $this->assertSame($value, $input->getAttribute('value'));
            $this->assertTrue($input->hasAttribute('data-date-picker-input'));
            $this->assertCount(1, $document->query('//label[@for="'.$input->getAttribute('id').'"]'));
        }
        $this->assertCount(2, $document->query('//*[@data-date-picker]'));
    }

    private function document(string $html): DOMXPath
    {
        $document = new DOMDocument;
        @$document->loadHTML('<?xml encoding="UTF-8">'.$html);

        return new DOMXPath($document);
    }

    private function element(DOMXPath $document, string $query): DOMElement
    {
        $element = $document->query($query)->item(0);
        $this->assertInstanceOf(DOMElement::class, $element, 'Missing element '.$query);

        return $element;
    }
}
