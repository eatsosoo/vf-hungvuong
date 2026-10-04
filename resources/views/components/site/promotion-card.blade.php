@props(['promotion', 'summary', 'featured' => false])
@php
    $daysRemaining = max(0, (int) now()->startOfDay()->diffInDays($promotion->ends_at->copy()->startOfDay(), false));
@endphp
<article @class(['offer-card', 'offer-card-featured' => $featured])>
    <a class="offer-card-visual" href="{{ route($clientRoutePrefix.'promotions.show', $promotion->slug) }}"
        tabindex="-1" aria-hidden="true">
        <span class="offer-visual-label">{{ $featured ? __('Khám phá ưu đãi') : __('VinFast Hùng Vương') }}</span>
        @if($promotion->media)
            <img src="{{ $promotion->media->url() }}" alt=""
                width="{{ $promotion->media->width ?: 900 }}" height="{{ $promotion->media->height ?: 600 }}"
                loading="{{ $featured ? 'eager' : 'lazy' }}" decoding="async">
        @else
            <x-admin.icon name="car" class="offer-image-placeholder" />
        @endif
        <span class="offer-visual-caption">
            {{ $promotion->vehicles->take(3)->pluck('name')->join(' · ') ?: __('Cùng bạn trên mọi hành trình') }}
        </span>
    </a>
    <div class="offer-card-body">
        <div class="offer-card-labels">
            <span class="offer-status"><span></span>{{ __('Đang áp dụng') }}</span>
            @if($summary['is_demo'])
                <span class="offer-demo">{{ __('Minh họa') }}</span>
            @endif
            @if($daysRemaining <= 7)
                <span class="offer-deadline">
                    {{ $daysRemaining ? __('Còn :days ngày', ['days' => $daysRemaining]) : __('Đến hết hôm nay') }}
                </span>
            @endif
        </div>
        <h3><a
            href="{{ route($clientRoutePrefix.'promotions.show', $promotion->slug) }}">
            {{ $promotion->title }}
        </a></h3>
        @if($featured && $summary['summary'])
            <p class="offer-summary">{{ $summary['summary'] }}</p>
        @endif
        @if($summary['benefits'])
            <ul class="offer-benefits" aria-label="{{ __('Quyền lợi nổi bật') }}">
                @foreach(array_slice($summary['benefits'], 0, $featured ? 3 : 2) as $benefit)
                    <li><x-admin.icon name="check" class="size-4" /><span>{{ $benefit }}</span></li>
                @endforeach
            </ul>
        @endif
        <div class="offer-models" aria-label="{{ __('Dòng xe áp dụng') }}">
            @forelse($promotion->vehicles as $applicableVehicle)
                <a
                    href="{{ route($clientRoutePrefix.'vehicles.show', $applicableVehicle->slug) }}">
                    {{ $applicableVehicle->name }}
                </a>
            @empty
                <span>{{ __('Liên hệ để được tư vấn xe áp dụng') }}</span>
            @endforelse
        </div>
        <div class="offer-card-footer">
            <p>
                <x-admin.icon name="calendar" class="size-4" />
                <span>{{ __('Đến') }} <time datetime="{{ $promotion->ends_at->toDateString() }}">
                    {{ $promotion->ends_at->format('d/m/Y') }}
                </time></span>
            </p>
            <a class="{{ $featured ? 'client-button' : 'offers-text-link' }}"
                href="{{ route($clientRoutePrefix.'promotions.show', $promotion->slug) }}"
                aria-label="{{ __('Xem ưu đãi: :title', ['title' => $promotion->title]) }}">
                {{ __('Xem ưu đãi') }} <x-admin.icon name="arrow" class="size-4" />
            </a>
        </div>
    </div>
</article>
