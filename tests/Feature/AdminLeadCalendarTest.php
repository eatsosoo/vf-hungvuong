<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\User;
use App\Models\Vehicle;
use Carbon\CarbonImmutable;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AdminLeadCalendarTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_test_drive_navigation_opens_calendar_and_other_leads_keep_list(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $this->get(route('admin.leads.index', ['type' => 'test_drive']))
            ->assertOk()->assertViewIs('admin.leads.calendar')->assertSee('Chưa có lịch lái thử trong tháng');
        $this->get(route('admin.leads.index'))->assertOk()->assertViewIs('admin.leads.index');
        $this->get(route('admin.leads.index', ['type' => 'quote', 'view' => 'calendar']))
            ->assertOk()->assertViewIs('admin.leads.index');
    }

    public function test_calendar_is_limited_to_staff_with_lead_permissions(): void
    {
        $url = route('admin.leads.index', ['type' => 'test_drive']);
        $this->get($url)->assertRedirect(route('login'));
        $this->actingAs(User::factory()->editor()->create())->get($url)->assertForbidden();
        $this->actingAs(User::factory()->admin()->inactive()->create())->get($url)->assertForbidden();
        $this->actingAs(User::factory()->manager()->create())->get($url)->assertOk();
    }

    public function test_calendar_uses_monday_first_dates_and_includes_only_the_visible_date_range(): void
    {
        $admin = User::factory()->admin()->create();
        foreach ([
            'Start of grid' => '2026-09-28 00:00:00',
            'First of month' => '2026-10-01 00:00:00',
            'Last of month' => '2026-10-31 23:59:59',
            'End of grid' => '2026-11-01 23:59:59',
            'Before grid' => '2026-09-27 23:59:59',
            'After grid' => '2026-11-02 00:00:00',
        ] as $name => $date) {
            Lead::factory()->create(['name' => $name, 'type' => 'test_drive', 'appointment_at' => $date]);
        }
        Lead::factory()->create([
            'name' => 'Not a test drive', 'type' => 'quote', 'appointment_at' => '2026-10-03 10:00:00',
        ]);
        $response = $this->actingAs($admin)->get(route('admin.leads.index', [
            'type' => 'test_drive', 'month' => '2026-10',
        ]))->assertOk()->assertSee('Start of grid')->assertSee('End of grid')
            ->assertDontSee('Before grid')->assertDontSee('After grid')->assertDontSee('Not a test drive');
        $calendar = $response->viewData('calendar');
        $this->assertSame('2026-09-28', $calendar['weeks']->first()->first()['date']->format('Y-m-d'));
        $this->assertSame('2026-11-01', $calendar['weeks']->last()->last()['date']->format('Y-m-d'));
        $this->assertSame(2, $calendar['total']);
        $this->assertCount(2, $calendar['agenda']);
    }

    public function test_calendar_prefers_scheduled_appointments_and_falls_back_to_customer_proposals(): void
    {
        $vehicle = Vehicle::factory()->create(['name' => 'VF 8']);
        $lead = Lead::factory()->create([
            'type' => 'test_drive', 'name' => 'Customer scheduled', 'vehicle_id' => $vehicle->id,
            'preferred_at' => '2026-09-15 09:00:00', 'appointment_at' => '2026-10-04 14:30:00',
            'status' => 'confirmed',
        ]);
        Lead::factory()->create([
            'type' => 'test_drive', 'name' => 'Customer proposed', 'preferred_at' => '2026-10-04 08:30:00',
        ]);
        Lead::factory()->create([
            'type' => 'test_drive', 'name' => 'Appointment overrides proposal',
            'preferred_at' => '2026-10-04 11:00:00', 'appointment_at' => '2026-12-01 11:00:00',
        ]);
        Lead::factory()->create(['type' => 'test_drive', 'name' => 'Customer unscheduled']);
        $response = $this->actingAs(User::factory()->admin()->create())->get(route('admin.leads.index', [
            'type' => 'test_drive', 'month' => '2026-10',
        ]))->assertOk()->assertSee('14:30')->assertSee('08:30')->assertSee('Đề xuất')
            ->assertSee('VF 8')->assertSee(route('admin.leads.edit', $lead))
            ->assertDontSee('Appointment overrides proposal')->assertDontSee('Customer unscheduled');
        $calendar = $response->viewData('calendar');
        $this->assertSame(2, $calendar['total']);
        $this->assertSame(1, $calendar['confirmed']);
        $this->assertSame(1, $calendar['pending']);
        $this->assertSame(1, $calendar['unscheduled']);
        $this->assertSame('Customer proposed', $calendar['agenda']->first()['appointments']->first()->name);
        $response->assertSee('2026-10-04T14:30:00+07:00');
    }

    public function test_sales_calendar_and_unscheduled_count_only_include_assigned_leads(): void
    {
        $sales = User::factory()->sales()->create();
        $other = User::factory()->sales()->create();
        Lead::factory()->create([
            'type' => 'test_drive', 'name' => 'Assigned customer', 'assigned_to' => $sales->id,
            'appointment_at' => '2026-10-04 10:00:00',
        ]);
        Lead::factory()->create([
            'type' => 'test_drive', 'name' => 'Other customer', 'assigned_to' => $other->id,
            'appointment_at' => '2026-10-04 11:00:00',
        ]);
        Lead::factory()->create(['type' => 'test_drive', 'assigned_to' => $sales->id]);
        Lead::factory()->count(2)->create(['type' => 'test_drive', 'assigned_to' => $other->id]);
        $response = $this->actingAs($sales)->get(route('admin.leads.index', [
            'type' => 'test_drive', 'month' => '2026-10',
        ]))->assertOk()->assertSee('Assigned customer')->assertDontSee('Other customer');
        $this->assertSame(1, $response->viewData('calendar')['total']);
        $this->assertSame(1, $response->viewData('calendar')['unscheduled']);
    }

    public function test_calendar_filters_and_month_navigation_preserve_search_and_status(): void
    {
        Lead::factory()->create([
            'type' => 'test_drive', 'name' => 'Lan Nguyen', 'status' => 'confirmed',
            'appointment_at' => '2026-10-04 10:00:00',
        ]);
        Lead::factory()->create([
            'type' => 'test_drive', 'name' => 'Lan pending', 'status' => 'new',
            'appointment_at' => '2026-10-04 11:00:00',
        ]);
        Lead::factory()->create([
            'type' => 'test_drive', 'name' => 'Another customer', 'status' => 'confirmed',
            'appointment_at' => '2026-10-04 12:00:00',
        ]);
        $response = $this->actingAs(User::factory()->admin()->create())->get(route('admin.leads.index', [
            'type' => 'test_drive', 'month' => '2026-10', 'status' => 'confirmed', 'q' => 'Lan',
        ]))->assertOk()->assertSee('Lan Nguyen')->assertDontSee('Lan pending')->assertDontSee('Another customer');
        $document = new DOMDocument;
        @$document->loadHTML('<?xml encoding="UTF-8">'.$response->getContent());
        $navigation = new DOMXPath($document);
        foreach (['prev' => '2026-09', 'next' => '2026-11'] as $direction => $month) {
            $link = $navigation->query('//nav[@aria-label="Điều hướng tháng"]//a[@rel="'.$direction.'"]');
            $this->assertCount(1, $link);
            parse_str(parse_url($link->item(0)->getAttribute('href'), PHP_URL_QUERY), $query);
            $this->assertSame($month, $query['month']);
            $this->assertSame('Lan', $query['q']);
            $this->assertSame('confirmed', $query['status']);
            $this->assertSame('test_drive', $query['type']);
        }
    }

    public function test_invalid_months_and_display_modes_are_rejected(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        foreach (['2026-13', '2026-00', '2026-2', '2026-10-01', '0000-01', '2100-01', 'invalid'] as $month) {
            $this->getJson(route('admin.leads.index', ['type' => 'test_drive', 'month' => $month]))
                ->assertUnprocessable()->assertJsonValidationErrors('month');
        }
        $this->getJson(route('admin.leads.index', ['type' => 'test_drive', 'view' => 'unknown']))
            ->assertUnprocessable()->assertJsonValidationErrors('view');
    }

    public function test_calendar_defaults_to_application_month_and_handles_leap_days(): void
    {
        $this->travelTo(CarbonImmutable::parse('2028-02-29 23:30:00', config('app.timezone')));
        Lead::factory()->create(['type' => 'test_drive', 'preferred_at' => '2028-02-29 23:00:00']);
        $response = $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.leads.index', ['type' => 'test_drive']))->assertOk();
        $calendar = $response->viewData('calendar');
        $this->assertSame('2028-02', $calendar['month']->format('Y-m'));
        $this->assertSame('2028-02-29', $calendar['agenda']->first()['date']->format('Y-m-d'));
        $this->assertTrue($calendar['agenda']->first()['isToday']);
        $this->assertSame('2028-01', $calendar['previousMonth']);
        $this->assertSame('2028-03', $calendar['nextMonth']);
        $this->travelBack();
    }

    public function test_list_mode_remains_paginated_with_calendar_navigation(): void
    {
        Lead::factory()->count(21)->create(['type' => 'test_drive']);
        $response = $this->actingAs(User::factory()->admin()->create())->get(route('admin.leads.index', [
            'type' => 'test_drive', 'view' => 'list',
        ]))->assertOk()->assertViewIs('admin.leads.index')->assertSee('Lịch tháng');
        $this->assertCount(20, $response->viewData('leads'));
        $this->assertSame(21, $response->viewData('leads')->total());
        $this->assertStringContainsString('view=list', $response->viewData('leads')->nextPageUrl());
    }

    public function test_calendar_without_times_shows_empty_state_and_unscheduled_count(): void
    {
        Lead::factory()->count(2)->create(['type' => 'test_drive']);
        $response = $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.leads.index', ['type' => 'test_drive']))->assertOk()
            ->assertSee('Chưa có lịch lái thử trong tháng')->assertSee('2 yêu cầu chưa có thời gian');
        $this->assertSame(0, $response->viewData('calendar')['total']);
        $this->assertCount(0, $response->viewData('calendar')['agenda']);
    }
}
