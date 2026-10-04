@extends('layouts.site')
@section('title', __('Ưu đãi VinFast · Hùng Vương'))
@section('description', __('Khám phá ưu đãi theo dòng xe, quyền lợi và thời gian áp dụng tại VinFast Hùng Vương.'))
@section('full_width', 'true')
@section('content')
    <section class="offers-intro" aria-labelledby="offers-title">
        <div class="client-container">
            <x-site.breadcrumbs :items="[
                ['label' => 'Trang chủ', 'url' => route($clientRoutePrefix.'home')],
                ['label' => 'Ưu đãi'],
            ]" dark />
            <div class="offers-intro-layout">
                <div>
                    <p class="client-eyebrow text-brand">{{ __('Ưu đãi & trải nghiệm') }}</p>
                    <h1 id="offers-title">{{ __('Thêm lý do để') }}<br>{{ __('bắt đầu hành trình.') }}</h1>
                    <p class="offers-intro-description">
                        {{ __('Tìm quyền lợi phù hợp với chiếc xe bạn yêu thích. Từ quà tặng, trải '
                            .'nghiệm lái thử đến đồng hành khi nhận xe.') }}
                    </p>
                </div>
                <a class="offers-intro-link" href="#offers-list">
                    <span class="offers-intro-icon"><x-admin.icon name="tag" class="size-6" /></span>
                    <span>
                        <strong>{{ __('Ưu đãi cho chiếc xe của bạn') }}</strong>
                        <span>{{ __('Chọn dòng xe, xem ngay quyền lợi') }}</span>
                    </span>
                    <x-admin.icon name="arrow" class="size-5" />
                </a>
            </div>
        </div>
    </section>

    <div class="client-container offers-content">
        <section id="offers-list" class="scroll-mt-8" aria-labelledby="offers-list-title">
            <div class="offers-section-heading">
                <div>
                    <p class="client-eyebrow">{{ __('Dành cho hành trình của bạn') }}</p>
                    <h2 id="offers-list-title">
                        {{
                            $activeVehicle ? __('Ưu đãi cho :vehicle',
                             ['vehicle' => $activeVehicle->name]) : __('Tìm ưu đãi phù hợp')
                        }}
                    </h2>
                </div>
                <p class="offers-result-count">{{ $promotions->total() }} {{ __('chương trình') }}</p>
            </div>
            <nav class="offers-filters" aria-label="{{ __('Lọc ưu đãi theo dòng xe') }}">
                <a class="offers-filter" href="{{ route($clientRoutePrefix.'promotions.index') }}#offers-list"
                    @unless($activeVehicle) aria-current="page" @endunless>{{ __('Tất cả dòng xe') }}</a>
                @foreach($promotionVehicles as $filterVehicle)
                    <a class="offers-filter"

                            href="{{
                                route($clientRoutePrefix.'promotions.index',
                                 ['vehicle' => $filterVehicle->slug])
                            }}#offers-list"
                        @if($activeVehicle?->id === $filterVehicle->id) aria-current="page" @endif>
                        {{ $filterVehicle->name }}
                        <span>{{ $filterVehicle->promotions_count }}</span>
                    </a>
                @endforeach
            </nav>

            @if(collect($promotionSummaries)->contains(fn ($summary) => $summary['is_demo']))
                <p class="offers-demo-note">
                    <x-admin.icon name="eye" class="size-4" />
                    {{ __('Các chương trình có nhãn “Minh họa” là nội dung mẫu để trải nghiệm website.') }}
                </p>
            @endif

            @if($promotions->isNotEmpty())
                @if($promotions->onFirstPage())
                    <x-site.promotion-card :promotion="$promotions->first()"
                        :summary="$promotionSummaries[$promotions->first()->id]" featured />
                @endif
                <div class="offers-grid">
                    @foreach($promotions as $promotion)
                        @continue($promotions->onFirstPage() && $loop->first)
                        <x-site.promotion-card :promotion="$promotion"
                            :summary="$promotionSummaries[$promotion->id]" />
                    @endforeach
                </div>
                <x-site.pagination :paginator="$promotions" />
            @else
                <x-site.empty-state title="{{ __('Chưa có ưu đãi cho dòng xe này.') }}"
                    description="{{ __('Xem các chương trình khác hoặc trao đổi cùng đại lý để được tư vấn '
                        .'theo nhu cầu.') }}">
                    <a class="client-button"
                        href="{{ route($clientRoutePrefix.'promotions.index') }}">
                        {{ __('Xem tất cả ưu đãi') }}
                    </a>
                    <a class="client-button secondary"

                            href="{{ route($clientRoutePrefix.'contact', ['type' => 'consultation']) }}">
                            {{ __('Nhận tư vấn') }}
                        </a>
                </x-site.empty-state>
            @endif
        </section>

        <section class="offers-guidance" aria-labelledby="offers-guide-title">
            <div class="offers-section-heading">
                <div>
                    <p class="client-eyebrow">{{ __('Dễ chọn hơn, an tâm hơn') }}</p>
                    <h2 id="offers-guide-title">{{ __('Một vài gợi ý trước khi quyết định.') }}</h2>
                </div>
            </div>
            <div class="offers-guide-grid">
                <a class="offers-guide-card" href="{{ route($clientRoutePrefix.'vehicles.index') }}">
                    <span class="offers-guide-icon"><x-admin.icon name="car" class="size-6" /></span>
                    <h3>{{ __('Chọn xe theo nhu cầu') }}</h3>
                    <p>{{ __('Đi phố mỗi ngày hay những chuyến đi gia đình? Khám phá thông số và '
                        .'phiên bản phù hợp.') }}</p>
                    <span class="offers-text-link">
                        {{ __('Khám phá dòng xe') }} <x-admin.icon name="arrow" class="size-4" /></span>
                </a>
                <a class="offers-guide-card" data-test-drive-open
                    href="{{ route($clientRoutePrefix.'contact', array_filter([
                        'type' => 'test_drive', 'vehicle' => $activeVehicle?->id,
                    ])) }}">
                    <span class="offers-guide-icon"><x-admin.icon name="calendar" class="size-6" /></span>
                    <h3>{{ __('Trải nghiệm trước khi chọn') }}</h3>
                    <p>{{ __('Tự cảm nhận không gian, khả năng vận hành và các tính năng trong một '
                        .'buổi lái thử.') }}</p>
                    <span class="offers-text-link">
                        {{ __('Đặt lịch lái thử') }} <x-admin.icon name="arrow" class="size-4" /></span>
                </a>
                <a class="offers-guide-card"

                        href="{{
                            route($clientRoutePrefix.'contact',
                             array_filter(['type' => 'quote',
                             'vehicle' => $activeVehicle?->id]))
                        }}">
                    <span class="offers-guide-icon"><x-admin.icon name="file" class="size-6" /></span>
                    <h3>{{ __('Hiểu rõ chi phí nhận xe') }}</h3>
                    <p>{{ __('Nhận tư vấn về giá xe, chi phí lăn bánh và điều kiện ưu đãi cho phiên '
                        .'bản bạn quan tâm.') }}</p>
                    <span class="offers-text-link">
                        {{ __('Nhận báo giá') }} <x-admin.icon name="arrow" class="size-4" /></span>
                </a>
            </div>
        </section>

        <section class="offers-faq" aria-labelledby="offers-faq-title">
            <div>
                <p class="client-eyebrow">{{ __('Thông tin cần biết') }}</p>
                <h2 id="offers-faq-title">{{ __('Bạn đang băn khoăn?') }}</h2>
                <p>{{ __('Đội ngũ Hùng Vương sẽ giúp bạn làm rõ quyền lợi trước khi lựa chọn.') }}</p>
                <a class="offers-text-link"
                    href="{{ route($clientRoutePrefix.'contact', ['type' => 'consultation']) }}">
                    {{ __('Trao đổi với tư vấn viên') }} <x-admin.icon name="arrow" class="size-4" />
                </a>
            </div>
            <div class="offers-faq-items">
                <details>
                    <summary>
                        {{
                            __('Có thể kết hợp nhiều ưu đãi không?')
                        }}
                        <x-admin.icon name="plus" class="size-4" /></summary>
                    <p>
                        {{ __('Việc kết hợp phụ thuộc điều kiện của từng chương trình và phiên bản '
                            .'xe. Tư vấn viên sẽ xác nhận các quyền lợi có thể áp dụng trong báo giá '
                            .'của bạn.') }}
                    </p>
                </details>
                <details>
                    <summary>
                        {{ __('Làm thế nào để nhận tư vấn đúng ưu đãi?') }}<x-admin.icon name="plus" class="size-4" />
                    </summary>
                    <p>
                        {{ __('Mở chương trình bạn quan tâm để xem xe áp dụng và thời hạn, sau đó '
                            .'chọn “Nhận tư vấn”. Bạn có thể ghi tên chương trình và thời gian dự '
                            .'kiến nhận xe trong lời nhắn.') }}
                    </p>
                </details>
                <details>
                    <summary>
                        {{
                            __('Chưa chọn được xe, tôi có thể lái thử trước?')
                        }}
                        <x-admin.icon name="plus" class="size-4" />
                    </summary>
                    <p>
                        {{ __('Bạn có thể đăng ký lái thử ngay trên trang này. Đại lý sẽ liên hệ xác '
                            .'nhận mẫu xe và khung giờ phù hợp trước buổi trải nghiệm.') }}
                    </p>
                </details>
            </div>
        </section>

        <x-site.cta title="{{ __('Ưu đãi phù hợp bắt đầu từ nhu cầu của bạn.') }}"
            description="{{ __('Chia sẻ dòng xe yêu thích và kế hoạch nhận xe. Hùng Vương sẽ cùng bạn '
                .'tìm phương án phù hợp.') }}"
            :vehicle="$activeVehicle?->id" />
    </div>
@endsection
