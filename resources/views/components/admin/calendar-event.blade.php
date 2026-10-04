@props(['lead', 'compact' => false])
@php
    $date = ($lead->appointment_at ?? $lead->preferred_at)->timezone(config('app.timezone'));
    $tones = [
        'new' => 'border-amber-200 bg-amber-50',
        'contacted' => 'border-amber-200 bg-amber-50',
        'confirmed' => 'border-green-200 bg-green-50',
        'completed' => 'border-sky-200 bg-sky-50',
        'cancelled' => 'border-line bg-paper',
    ];
@endphp
<a href="{{ route('admin.leads.edit', $lead) }}"
    @class([
        'block min-h-11 rounded-xl border p-3 text-ink! transition-colors hover:border-green-text',
        $tones[$lead->status->value] ?? 'border-line bg-white',
    ])>
    <span class="flex flex-wrap items-center gap-x-2 gap-y-1 text-sm font-bold">
        <time datetime="{{ $date->toIso8601String() }}" class="tabular-nums">{{ $date->format('H:i') }}</time>
        <span class="text-xs font-medium text-muted">{{ $lead->appointment_at ? 'Lịch hẹn' : 'Đề xuất' }}</span>
    </span>
    <span class="mt-1 block text-sm font-bold">{{ $lead->name }}</span>
    <span class="mt-1 block text-xs text-muted">
        {{ $lead->vehicle?->name ?? 'Chưa chọn xe' }}
        @if($lead->variant) · {{ $lead->variant->name }} @endif
    </span>
    <span class="mt-2 block text-xs font-semibold">{{ __('studio.status.'.$lead->status->value) }}</span>
    @unless($compact)
        <span class="mt-2 block text-sm text-muted">
            Phụ trách: {{ $lead->assignee?->name ?? 'Chưa phân công' }}
        </span>
        @if($lead->location)
            <span class="mt-1 block text-sm text-muted">Địa điểm: {{ $lead->location }}</span>
        @endif
    @endunless
</a>
