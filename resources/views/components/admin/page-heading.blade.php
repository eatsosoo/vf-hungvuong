@props(['title', 'description' => null, 'eyebrow' => 'Quản trị đại lý'])
<div class="page-heading">
    <div class="min-w-0">
        <p class="eyebrow">{{ $eyebrow }}</p>
        <h1>{{ $title }}</h1>
        @if($description)
            <p class="mt-3 mb-0! max-w-2xl text-muted">{{ $description }}</p>
        @endif
    </div>
    @if($slot->isNotEmpty())
        <div class="actions">{{ $slot }}</div>
    @endif
</div>
