@props(['vehicle', 'colors', 'selectedColor' => null])

@php
    $selectedMedia = $selectedColor?->media ?? $vehicle->media;
@endphp

<div id="colors" class="vehicle-color-viewer scroll-mt-28" data-vehicle-color-viewer>
    <figure class="vehicle-color-stage" data-vehicle-stage
        aria-label="{{ __('Hình ảnh :vehicle', ['vehicle' => $vehicle->name]) }}">
        @if($selectedMedia)
            <img
                class="vehicle-color-image"
                data-vehicle-image
                src="{{ $selectedMedia->url() }}"
                alt="{{ $selectedColor ? __(':vehicle màu :color', [
                    'vehicle' => $vehicle->name, 'color' => $selectedColor->name,
                ]) : $vehicle->name }}"
                width="{{ $selectedMedia->width ?: 1200 }}"
                height="{{ $selectedMedia->height ?: 800 }}"
                fetchpriority="high"
                decoding="async"
            >
            <img
                class="vehicle-color-image vehicle-color-image-outgoing"
                data-vehicle-image-outgoing
                alt=""
                aria-hidden="true"
                hidden
            >
        @else
            <div class="vehicle-color-placeholder">
                <x-admin.icon name="car" class="size-24 text-white/40" />
                <p>Hình ảnh {{ $vehicle->name }} đang được cập nhật.</p>
            </div>
        @endif
        <span class="vehicle-color-loading" data-vehicle-color-loading hidden aria-hidden="true">
            <span></span>
            {{ __('Đang tải màu xe') }}
        </span>
    </figure>

    @if($colors->isNotEmpty())
        <div class="vehicle-color-controls">
            <div class="vehicle-color-caption">
                <div>
                    <p class="vehicle-color-eyebrow">{{ __('Màu ngoại thất') }}</p>
                    <p class="vehicle-color-name" data-vehicle-color-name aria-live="polite" aria-atomic="true">
                        {{ $selectedColor?->name }}
                    </p>
                </div>
                <span class="vehicle-color-count">{{ $colors->count() }} {{ __('màu') }}</span>
            </div>
            <div class="vehicle-color-options" aria-label="{{ __('Chọn màu ngoại thất') }}">
                @foreach($colors as $color)
                    @php
                        $primaryHex = preg_match('/\A#[0-9a-fA-F]{6}\z/', $color->hex ?? '')
                            ? $color->hex : '#a0a5a5';
                        $secondaryHex = preg_match('/\A#[0-9a-fA-F]{6}\z/', $color->secondary_hex ?? '')
                            ? $color->secondary_hex : $primaryHex;
                    @endphp
                    <a
                        class="vehicle-color-option"

                            href="{{
                                route($clientRoutePrefix.'vehicles.show',
                                 ['slug' => $vehicle->slug,
                                 'color' => $color->id])
                            }}#colors"
                        data-vehicle-color
                        data-image="{{ $color->media->url() }}"
                        data-name="{{ $color->name }}"
                        data-alt="{{
                            __(':vehicle màu :color',
                             ['vehicle' => $vehicle->name,
                             'color' => $color->name])
                        }}"
                        aria-label="{{ __('Chọn màu :color', ['color' => $color->name]) }}"
                        aria-current="{{ $selectedColor?->id === $color->id ? 'true' : 'false' }}"
                        title="{{ $color->name }}"
                    >
                        <svg class="vehicle-color-swatch" viewBox="0 0 32 32" aria-hidden="true" focusable="false">
                            <rect width="32" height="32" fill="{{ $primaryHex }}" />
                            <path d="M0 0H32V32Z" fill="{{ $secondaryHex }}" />
                        </svg>
                        <span class="vehicle-color-check" aria-hidden="true">
                            <x-admin.icon name="check" class="size-3" />
                        </span>
                    </a>
                @endforeach
            </div>
            <p class="vehicle-color-hint">{{ __('Chọn màu để khám phá. Màu thực tế có thể khác theo ánh sáng.') }}</p>
            <p class="vehicle-color-feedback sr-only" data-vehicle-color-feedback role="status" aria-live="polite"></p>
        </div>
    @endif
</div>
