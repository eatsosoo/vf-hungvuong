@props(['vehicles' => []])
<div id="vehicle-menu" class="vehicle-menu" data-vehicle-menu hidden>
    <div class="vehicle-menu-backdrop" data-vehicle-menu-close></div>
    <section class="vehicle-menu-panel" aria-label="{{ __('Dòng xe') }}" data-vehicle-menu-panel>
        <div class="vehicle-menu-content">
            <aside class="vehicle-menu-sidebar">
                <p class="vehicle-menu-eyebrow">{{ __('Dòng xe VinFast') }}</p>
                <a class="vehicle-menu-all" href="{{ route($clientRoutePrefix.'vehicles.index') }}">
                    {{ __('Tất cả dòng xe') }} <x-admin.icon name="arrow" />
                </a>
                <div class="vehicle-menu-tools">
                    <p>{{ __('Công cụ hỗ trợ khách hàng') }}</p>
                    <a href="{{ route($clientRoutePrefix.'vehicles.compare') }}">
                        <x-admin.icon name="car" /> {{ __('So sánh xe') }}
                    </a>
                    <a data-test-drive-open href="{{ route($clientRoutePrefix.'contact', ['type' => 'test_drive']) }}">
                        <x-admin.icon name="arrow" /> {{ __('Đăng ký lái thử') }}
                    </a>
                </div>
            </aside>
            <div class="vehicle-menu-catalog">
                <div class="vehicle-menu-grid">
                    @forelse($vehicles as $vehicle)
                        <a class="vehicle-menu-card"
                            href="{{ route($clientRoutePrefix.'vehicles.show', $vehicle->slug) }}">
                            <div class="vehicle-menu-image">
                                @if($vehicle->media)
                                    <img src="{{ $vehicle->media->url() }}"
                                        alt="{{ $vehicle->media->alt ?: $vehicle->name }}"
                                        width="{{ $vehicle->media->width ?: 900 }}"
                                        height="{{ $vehicle->media->height ?: 600 }}" decoding="async" loading="lazy">
                                @else
                                    <x-admin.icon name="car" />
                                @endif
                            </div>
                            <span class="vehicle-menu-name">{{ $vehicle->name }}</span>
                            <span class="vehicle-menu-badges">
                                @php($seats = $vehicle->specifications['Số chỗ'] ?? null)
                                @if($seats && is_numeric($seats))
                                    <span>{{ $seats }} {{ __('chỗ') }}</span>
                                @endif
                                @if($vehicle->segment)
                                    <span>{{ $vehicle->segment }}</span>
                                @endif
                            </span>
                        </a>
                    @empty
                        <p>{{ __('Thông tin xe đang được cập nhật.') }}</p>
                    @endforelse
                </div>
            </div>
        </div>
    </section>
</div>
