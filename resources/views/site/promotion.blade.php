@extends('layouts.site')
@section('title', $promotion->title.' · VinFast Hùng Vương')
@section('description', $promotionSummary['summary'])
@section('content')
    <x-site.breadcrumbs :items="[
        ['label' => 'Trang chủ', 'url' => route($clientRoutePrefix.'home')],
        ['label' => 'Ưu đãi', 'url' => route($clientRoutePrefix.'promotions.index')],
        ['label' => $promotion->title],
    ]" />
    <div class="offer-detail-header">
        <div class="offer-card-labels">
            <span class="offer-status"><span></span>{{ __('Đang áp dụng') }}</span>
            @if($promotionSummary['is_demo'])
                <span class="offer-demo">{{ __('Chương trình minh họa') }}</span>
            @endif
        </div>
        <h1>{{ $promotion->title }}</h1>
        <p class="offer-detail-dates">
            <x-admin.icon name="calendar" />
            {{ $promotion->starts_at->format('d/m/Y') }} — {{ $promotion->ends_at->format('d/m/Y') }}
        </p>
    </div>

    <div class="offer-detail-layout">
        <article class="min-w-0">
            @if($promotion->media)
                <div class="offer-detail-image">
                    <img src="{{ $promotion->media->url() }}"
                        alt="{{ $promotion->media->alt ?: $promotion->title }}"
                        width="{{ $promotion->media->width ?: 900 }}"
                        height="{{ $promotion->media->height ?: 600 }}" decoding="async">
                </div>
            @endif
            <div class="client-prose">{!! $content['html'] !!}</div>
        </article>
        <aside class="offer-detail-aside" aria-labelledby="offer-contact-title">
            <div class="client-panel">
                <p class="client-eyebrow">{{ __('Quyền lợi dành cho bạn') }}</p>
                <h2 id="offer-contact-title">{{ __('Cùng tìm hiểu ưu đãi.') }}</h2>
                @if($promotionSummary['benefits'])
                    <ul class="offer-benefits">
                        @foreach($promotionSummary['benefits'] as $benefit)
                            <li><x-admin.icon name="check" class="size-4" /><span>{{ $benefit }}</span></li>
                        @endforeach
                    </ul>
                @endif
                <div class="offer-aside-period">
                    <x-admin.icon name="calendar" />
                    <div>
                        <span>{{ __('Thời hạn áp dụng') }}</span>
                        <strong>{{ __('Đến') }} {{ $promotion->ends_at->format('d/m/Y') }}</strong>
                    </div>
                </div>
                <a class="client-button w-full"
                    href="{{ route($clientRoutePrefix.'contact', array_filter([
                        'type' => 'consultation', 'vehicle' => $vehicles->count() === 1 ? $vehicles->first()->id : null,
                    ])) }}">{{ __('Nhận tư vấn ưu đãi') }} <x-admin.icon name="arrow" class="size-4" /></a>
                <a class="client-button secondary mt-3 w-full" data-test-drive-open
                    href="{{ route($clientRoutePrefix.'contact', array_filter([
                        'type' => 'test_drive', 'vehicle' => $vehicles->count() === 1 ? $vehicles->first()->id : null,
                    ])) }}">{{ __('Đặt lịch lái thử') }}</a>
                <p class="offer-aside-note">
                    {{ __('Điều kiện áp dụng sẽ được tư vấn cụ thể theo phiên bản và thời điểm nhận xe.') }}
                </p>
            </div>
        </aside>
    </div>

    @if($vehicles->isNotEmpty())
        <section class="offers-guidance" aria-labelledby="offer-models-title">
            <div class="offers-section-heading">
                <div>
                    <p class="client-eyebrow">{{ __('Chiếc xe bạn quan tâm') }}</p>
                    <h2 id="offer-models-title">{{ __('Dòng xe áp dụng') }}</h2>
                </div>
                <a class="offers-text-link" href="{{ route($clientRoutePrefix.'promotions.index') }}">
                    {{ __('Tất cả ưu đãi') }} <x-admin.icon name="arrow" class="size-4" />
                </a>
            </div>
            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                @foreach($vehicles as $vehicle)
                    <x-vehicle-card :vehicle="$vehicle" />
                @endforeach
            </div>
        </section>
    @endif
@endsection
