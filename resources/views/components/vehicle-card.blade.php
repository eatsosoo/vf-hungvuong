@props(['vehicle'])
@php($price = $vehicle->variants->whereNotNull('price')->min('price'))
<article class="flex min-w-0 flex-col overflow-hidden rounded-2xl border border-line bg-white">
    <a class="block bg-[#e9eee9] p-5" href="{{ route($clientRoutePrefix.'vehicles.show', $vehicle->slug) }}"
        tabindex="-1" aria-label="Khám phá {{ $vehicle->name }}">
        @if($vehicle->media)
            <img class="aspect-[3/2] w-full object-contain" src="{{ $vehicle->media->url() }}"
                alt="{{ $vehicle->media->alt ?: $vehicle->name }}"
                width="{{ $vehicle->media->width ?: 900 }}" height="{{ $vehicle->media->height ?: 600 }}"
                loading="lazy" decoding="async">
        @else
            <div class="flex aspect-[3/2] items-center justify-center text-muted">
                <x-admin.icon name="car" class="size-16 opacity-50" />
            </div>
        @endif
    </a>
    <div class="flex flex-1 flex-col p-6">
        <p class="client-eyebrow">{{ $vehicle->segment ?: __('Dòng xe VinFast') }}</p>
        <h3 class="mt-3">
            <a class="hover:text-green-text" href="{{ route($clientRoutePrefix.'vehicles.show', $vehicle->slug) }}">
                {{ $vehicle->name }}
            </a>
        </h3>
        <p class="mt-4 text-sm text-muted">{{ $price !== null ? __('Giá tham khảo từ') : __('Giá và ưu đãi hiện hành') }}</p>
        <p class="mt-1 text-xl font-bold">
            {{ $price !== null ? number_format((float) $price, 0, ',', '.').__('VNĐ') : __('Liên hệ nhận báo giá') }}
        </p>
        <a class="mt-6 flex min-h-11 items-center justify-between gap-2 border-t border-line pt-4
            text-sm font-bold text-green-text"
            href="{{ route($clientRoutePrefix.'vehicles.show', $vehicle->slug) }}">
            {{ __('Khám phá mẫu xe') }} <x-admin.icon name="arrow" class="size-4" />
        </a>
        <a class="mt-2 inline-flex min-h-11 items-center text-sm font-semibold text-green-text"
            href="{{ route($clientRoutePrefix.'vehicles.compare', ['first' => $vehicle->slug]) }}">
            {{ __('So sánh xe này') }}
        </a>
    </div>
</article>
