@props(['title' => 'Chưa có dữ liệu', 'description' => null, 'icon' => 'inbox'])
<div class="flex flex-col items-center gap-3 px-5 py-10 text-center">
    <span class="grid size-12 place-items-center rounded-2xl bg-paper text-green-text">
        <x-admin.icon :name="$icon" class="size-6" />
    </span>
    <p class="mb-0! font-semibold text-ink">{{ $title }}</p>
    @if($description)
        <p class="mb-0! max-w-md text-sm text-muted">{{ $description }}</p>
    @endif
    {{ $slot }}
</div>
