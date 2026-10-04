<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('view-reports');
        $data = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', Rule::when($request->filled('from'), 'after_or_equal:from')],
            'total_min' => ['nullable', 'integer', 'min:0'],
            'total_max' => ['nullable', 'integer', 'min:0', Rule::when($request->filled('total_min'), 'gte:total_min')],
            'per_page' => ['nullable', 'integer', Rule::in([10, 20, 50, 100])],
            'sort' => ['nullable', Rule::in(['day', 'total'])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
        ]);
        $query = Lead::query();
        foreach (['from' => '>=', 'to' => '<='] as $key => $operator) {
            if (filled($data[$key] ?? null)) {
                $query->whereDate('created_at', $operator, $data[$key]);
            }
        }
        $total = (clone $query)->count();
        $won = (clone $query)->where('status', 'won')->count();
        $daily = (clone $query)->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->groupByRaw('DATE(created_at)');
        foreach (['total_min' => '>=', 'total_max' => '<='] as $key => $operator) {
            if (isset($data[$key])) {
                $daily->havingRaw('COUNT(*) '.$operator.' ?', [(int) $data[$key]]);
            }
        }

        $daily->orderBy($data['sort'] ?? 'day', $data['direction'] ?? (isset($data['sort']) ? 'asc' : 'desc'));
        if (($data['sort'] ?? 'day') !== 'day') {
            $daily->orderByDesc('day');
        }

        return view('admin.reports.index', ['total' => $total, 'won' => $won,
            'conversion' => $total ? round($won * 100 / $total, 1) : 0,
            'daily' => $daily->paginate((int) ($data['per_page'] ?? 20))->withQueryString(),
            'sources' => (clone $query)->selectRaw('source, COUNT(*) as total')->groupBy('source')->get(),
            'vehicles' => (clone $query)->with('vehicle')->selectRaw('vehicle_id, COUNT(*) as total')
                ->groupBy('vehicle_id')->orderByDesc('total')->get(),
            'statuses' => (clone $query)->selectRaw('status, COUNT(*) as total')->groupBy('status')->get()]);
    }

    public function export(): StreamedResponse
    {
        Gate::authorize('view-reports');
        AuditLog::query()->create(['user_id' => auth()->id(), 'action' => 'export', 'subject_type' => Lead::class,
            'subject_id' => 0, 'changed_fields' => []]);

        return response()->streamDownload(function (): void {
            $stream = fopen('php://output', 'w');
            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, ['ID', 'Họ tên', 'Điện thoại', 'Email', 'Loại', 'Trạng thái', 'Nguồn'], ',', '"', '');
            foreach (Lead::query()->orderBy('id')->cursor() as $lead) {
                $row = [$lead->id,
                    $lead->name,
                    $lead->phone,
                    $lead->email,
                    $lead->type,
                    $lead->status->value,
                    $lead->source];
                $row = array_map(fn ($value) => preg_match('/^[\s]*[=+\-@]/u', (string) $value)
                    ? "'".$value : $value, $row);
                fputcsv($stream, $row, ',', '"', '');
            }
            fclose($stream);
        }, 'khach-hang.csv', ['Content-Type' => 'text/csv; charset=UTF-8', 'Cache-Control' => 'no-store']);
    }

    public function audit(Request $request): View
    {
        Gate::authorize('view-reports');
        $subjectTypes = AuditLog::query()->select('subject_type')->distinct()->orderBy('subject_type')
            ->pluck('subject_type');
        $data = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', Rule::when($request->filled('from'), 'after_or_equal:from')],
            'user_id' => ['nullable', 'regex:/^(system|[1-9]\d*)$/'],
            'action' => ['nullable', 'string', 'max:100'],
            'subject_type' => ['nullable', 'string', Rule::in($subjectTypes->all())],
            'subject_id' => ['nullable', 'integer', 'min:0'],
            'changed_field' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', Rule::in([10, 20, 50, 100])],
            'sort' => ['nullable', Rule::in([
                'created_at', 'actor', 'action', 'subject', 'changed_fields', 'id', 'updated_at',
            ])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
        ]);
        $query = AuditLog::query()->with('actor');
        foreach (['from' => '>=', 'to' => '<='] as $key => $operator) {
            if (filled($data[$key] ?? null)) {
                $query->whereDate('created_at', $operator, $data[$key]);
            }
        }
        if (($data['user_id'] ?? null) === 'system') {
            $query->whereNull('user_id');
        } elseif (filled($data['user_id'] ?? null)) {
            $query->where('user_id', $data['user_id']);
        }
        if (filled($data['action'] ?? null)) {
            $query->where('action', 'like', '%'.$data['action'].'%');
        }
        foreach (['subject_type', 'subject_id'] as $key) {
            if (isset($data[$key])) {
                $query->where($key, $data[$key]);
            }
        }
        if (filled($data['changed_field'] ?? null)) {
            $query->whereJsonContains('changed_fields', $data['changed_field']);
        }

        $direction = $data['direction'] ?? (isset($data['sort']) ? 'asc' : 'desc');
        $sortColumn = match ($data['sort'] ?? null) {
            'actor' => User::query()->select('name')->whereColumn('users.id', 'audit_logs.user_id'),
            'subject' => 'subject_type',
            default => $data['sort'] ?? 'created_at',
        };
        $query->orderBy($sortColumn, $direction);
        if (($data['sort'] ?? null) === 'subject') {
            $query->orderBy('subject_id', $direction);
        }
        $query->orderByDesc('id');

        return view('admin.reports.audit', [
            'logs' => $query->paginate((int) ($data['per_page'] ?? 20))
                ->withQueryString(),
            'actors' => User::query()->whereIn('id', AuditLog::query()->select('user_id')->whereNotNull('user_id'))
                ->orderBy('name')->get(),
            'subjectTypes' => $subjectTypes,
        ]);
    }
}
