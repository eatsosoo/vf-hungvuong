<?php

namespace App\Actions\Leads;

use App\Models\Lead;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class BuildLeadCalendar
{
    /**
     * @param  Builder<Lead>  $query
     * @return array{month: CarbonImmutable, previousMonth: ?string, nextMonth: ?string, todayMonth: string,
     *     weeks: Collection, agenda: Collection, total: int, confirmed: int, pending: int, unscheduled: int}
     */
    public function handle(Builder $query, CarbonImmutable $month): array
    {
        $month = $month->startOfMonth();
        $today = CarbonImmutable::now(config('app.timezone'))->startOfDay();
        $startsAt = $month->startOfWeek(CarbonInterface::MONDAY);
        $endsAt = $month->endOfMonth()->startOfDay()->endOfWeek(CarbonInterface::SUNDAY)->addDay()->startOfDay();
        $appointments = (clone $query)
            ->with('variant')
            ->where(function (Builder $query) use ($startsAt, $endsAt): void {
                $query->where(function (Builder $query) use ($startsAt, $endsAt): void {
                    $query->where('appointment_at', '>=', $startsAt)->where('appointment_at', '<', $endsAt);
                })->orWhere(function (Builder $query) use ($startsAt, $endsAt): void {
                    $query->whereNull('appointment_at')
                        ->where('preferred_at', '>=', $startsAt)->where('preferred_at', '<', $endsAt);
                });
            })
            ->orderByRaw('COALESCE(appointment_at, preferred_at)')
            ->orderBy('id')
            ->get();
        $byDate = $appointments->groupBy(fn (Lead $lead): string => $this->eventDate($lead)->format('Y-m-d'));
        $days = collect();
        for ($date = $startsAt; $date->lessThan($endsAt); $date = $date->addDay()) {
            $days->push([
                'date' => $date,
                'isCurrentMonth' => $date->format('Y-m') === $month->format('Y-m'),
                'isToday' => $date->isSameDay($today),
                'appointments' => $byDate->get($date->format('Y-m-d'), collect()),
            ]);
        }
        $monthDays = $days->where('isCurrentMonth', true);
        $monthAppointments = $monthDays->pluck('appointments')->flatten(1);

        return [
            'month' => $month,
            'previousMonth' => $month->year > 1900 || $month->month > 1
                ? $month->subMonth()->format('Y-m') : null,
            'nextMonth' => $month->year < 2099 || $month->month < 12
                ? $month->addMonth()->format('Y-m') : null,
            'todayMonth' => $today->format('Y-m'),
            'weeks' => $days->chunk(7),
            'agenda' => $monthDays->filter(fn (array $day): bool => $day['appointments']->isNotEmpty()),
            'total' => $monthAppointments->count(),
            'confirmed' => $monthAppointments->filter(
                fn (Lead $lead): bool => $lead->status->value === 'confirmed'
            )->count(),
            'pending' => $monthAppointments->filter(
                fn (Lead $lead): bool => in_array($lead->status->value, ['new', 'contacted'], true)
            )->count(),
            'unscheduled' => (clone $query)->whereNull('appointment_at')->whereNull('preferred_at')->count(),
        ];
    }

    private function eventDate(Lead $lead): CarbonInterface
    {
        return ($lead->appointment_at ?? $lead->preferred_at)->setTimezone(config('app.timezone'));
    }
}
