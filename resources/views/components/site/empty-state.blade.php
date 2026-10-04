@props(['title' => __('Chưa có nội dung phù hợp.'), 'description' => null])
<div class="client-panel py-12 text-center sm:py-16">
    <span class="mx-auto mb-5 flex size-14 items-center justify-center rounded-full bg-paper text-green-text">
        <x-admin.icon name="file" class="size-6" />
    </span>
    <h2 class="text-xl sm:text-2xl">{{ $title }}</h2>
    @if($description)
        <p class="mx-auto mt-3 max-w-lg text-muted">{{ $description }}</p>
    @endif
    {{ $slot }}
</div>
