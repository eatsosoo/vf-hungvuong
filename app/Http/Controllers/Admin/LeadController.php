<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Leads\BuildLeadCalendar;
use App\Actions\Leads\UpdateLead;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LeadRequest;
use App\Models\Lead;
use App\Models\User;
use App\Models\Vehicle;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LeadController extends Controller
{
    public function index(Request $request, BuildLeadCalendar $calendar): View
    {
        Gate::authorize('viewAny', Lead::class);
        $data = $request->validate([
            'status' => ['nullable', 'in:new,contacted,confirmed,completed,cancelled,won,lost'],
            'type' => ['nullable', 'in:consultation,quote,test_drive'],
            'q' => ['nullable', 'string', 'max:100'],
            'vehicle_id' => ['nullable', 'integer', 'exists:vehicles,id'],
            'assigned_to' => ['nullable', 'string', 'max:30', 'regex:/^(unassigned|[1-9]\d*)$/'],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'per_page' => ['nullable', 'integer', Rule::in([10, 20, 50, 100])],
            'sort' => ['nullable', Rule::in([
                'name', 'type', 'vehicle', 'assignee', 'status', 'time', 'id', 'updated_at', 'created_at',
            ])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
            'view' => ['nullable', 'in:list,calendar'],
            'month' => ['nullable', 'date_format:Y-m', 'regex:/^(19|20)\d{2}-(0[1-9]|1[0-2])$/'],
        ]);
        $query = Lead::query()->accessibleTo($request->user())->with(['vehicle', 'assignee']);
        foreach (['status', 'type', 'vehicle_id'] as $key) {
            if (! empty($data[$key])) {
                $query->where($key, $data[$key]);
            }
        }
        if (filled($data['q'] ?? null)) {
            $query->where('name', 'like', '%'.$data['q'].'%');
        }
        if (($data['assigned_to'] ?? null) === 'unassigned') {
            $query->whereNull('assigned_to');
        } elseif (! empty($data['assigned_to'])) {
            $query->where('assigned_to', $data['assigned_to']);
        }
        foreach (['date_from' => '>=', 'date_to' => '<='] as $key => $operator) {
            if (! empty($data[$key])) {
                $query->whereDate(DB::raw('COALESCE(appointment_at, preferred_at, created_at)'),
                    $operator, $data[$key]);
            }
        }

        if (($data['type'] ?? null) === 'test_drive' && ($data['view'] ?? 'calendar') === 'calendar') {
            $month = CarbonImmutable::createFromFormat(
                '!Y-m', $data['month'] ?? now()->format('Y-m'), config('app.timezone')
            );

            return view('admin.leads.calendar', [
                'calendar' => $calendar->handle($query, $month),
                'calendarFilters' => array_filter([
                    'type' => 'test_drive',
                    'view' => 'calendar',
                    'q' => $data['q'] ?? null,
                    'status' => $data['status'] ?? null,
                    'vehicle_id' => $data['vehicle_id'] ?? null,
                    'assigned_to' => $data['assigned_to'] ?? null,
                    'date_from' => $data['date_from'] ?? null,
                    'date_to' => $data['date_to'] ?? null,
                ], fn (mixed $value): bool => filled($value)),
            ]);
        }

        $sortColumn = match ($data['sort'] ?? null) {
            'vehicle' => Vehicle::query()->select('name')->whereColumn('vehicles.id', 'leads.vehicle_id'),
            'assignee' => User::query()->select('name')->whereColumn('users.id', 'leads.assigned_to'),
            'time' => DB::raw('COALESCE(appointment_at, preferred_at, created_at)'),
            default => $data['sort'] ?? 'created_at',
        };
        $query->orderBy($sortColumn, $data['direction'] ?? (isset($data['sort']) ? 'asc' : 'desc'))
            ->orderByDesc('id');

        return view('admin.leads.index', [
            'leads' => $query
                ->paginate((int) ($data['per_page'] ?? 20))->withQueryString(),
            'vehicles' => Vehicle::query()->orderBy('name')->get(),
            'staff' => User::query()->whereIn('role', ['admin', 'manager', 'sales'])
                ->when($request->user()->role === UserRole::Sales,
                    fn (Builder $staffQuery): Builder => $staffQuery->whereKey($request->user()->id))
                ->orderBy('name')->get(),
        ]);
    }

    public function edit(Request $request, Lead $lead): View
    {
        abort_unless($request->user()->can('update', $lead), 404);

        return view('admin.leads.form', ['lead' => $lead->load(['vehicle', 'variant', 'notes.author']),
            'staff' => User::query()->where('is_active', true)->whereIn('role', ['admin', 'manager', 'sales'])->get()]);
    }

    public function update(LeadRequest $request, Lead $lead, UpdateLead $action): RedirectResponse
    {
        $action->handle($request->user(), $lead, $request->validated());

        return back()->with('success', 'Đã cập nhật yêu cầu.');
    }
}
