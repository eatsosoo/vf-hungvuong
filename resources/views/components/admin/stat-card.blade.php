@props(['label', 'value', 'icon' => 'chart', 'href' => null, 'tone' => 'neutral'])
@if($href)
    <a class="stat-card" data-tone="{{ $tone }}" href="{{ $href }}">
@else
    <article class="stat-card" data-tone="{{ $tone }}">
@endif
    <div class="flex items-start justify-between gap-3">
        <span class="text-sm font-medium text-muted">{{ $label }}</span>
        <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-paper text-green-text">
            <x-admin.icon :name="$icon" />
        </span>
    </div>
    <strong class="stat">{{ $value }}</strong>
@if($href)
    </a>
@else
    </article>
@endif
