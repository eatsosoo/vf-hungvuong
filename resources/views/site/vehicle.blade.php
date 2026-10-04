@extends('layouts.site')
@section('full_width', 'true')
@section('title', $vehicle->name.' · VinFast Hùng Vương')
@section('description', Str::limit((string) $vehicle->description, 160))
@section('content')
    @php
        $startingPrice = $vehicle->variants->whereNotNull('price')->min('price');
        $specifications = $vehicle->specifications ?? [];
    @endphp
    <section class="overflow-hidden bg-night text-white" aria-labelledby="vehicle-title">
        <div class="client-container pt-6 pb-12 sm:pt-8 sm:pb-16">
            <x-site.breadcrumbs :items="$breadcrumbs" dark />
            <div class="mt-8 grid items-center gap-8 lg:grid-cols-[minmax(0,0.9fr)_minmax(0,1.2fr)] lg:gap-12">
                <div class="min-w-0">
                    <p class="client-eyebrow text-brand">{{ $vehicle->segment ?: __('Dòng xe VinFast') }}</p>
                    <h1
                        id="vehicle-title"
                        class="mt-4 leading-[1.13] font-bold tracking-tight text-balance"
                    >
                        {{ $vehicle->name }}
                    </h1>
                    @if($vehicle->description)
                        <p class="mt-5 max-w-xl text-base leading-7 text-white/75 sm:text-lg sm:leading-8">
                            {{ $vehicle->description }}
                        </p>
                    @endif
                    <div class="mt-7 border-l-2 border-brand pl-5">
                        <p class="text-sm text-white/65">
                            {{ $startingPrice !== null ? __('Giá tham khảo từ') : __('Giá và ưu đãi hiện hành') }}
                        </p>
                        <p class="mt-1 text-3xl font-bold tracking-tight tabular-nums sm:text-4xl">
                            {{ $startingPrice !== null
                                ? number_format((float) $startingPrice, 0, ',', '.') : __('Nhận báo giá') }}
                            @if($startingPrice !== null)
                                <span class="text-base font-normal text-white/65">{{ __('VNĐ') }}</span>
                            @endif
                        </p>
                    </div>
                    <div class="mt-8 flex flex-wrap gap-3">
                        <a
                            class="client-button"

                                href="{{
                                    route($clientRoutePrefix.'contact',
                                     ['type' => 'quote',
                                     'vehicle' => $vehicle->id])
                                }}"
                        >
                            {{ __('Nhận báo giá') }}
                            <x-admin.icon name="arrow" />
                        </a>
                        <a
                            class="client-button-secondary border-white/30 bg-transparent text-white hover:bg-white/10"
                            data-test-drive-open

                                href="{{
                                    route($clientRoutePrefix.'contact',
                                     ['type' => 'test_drive',
                                     'vehicle' => $vehicle->id])
                                }}"
                        >
                            <x-admin.icon name="calendar" />
                            {{ __('Đăng ký lái thử') }}
                        </a>
                    </div>
                </div>
                <x-site.vehicle-color-picker
                    :vehicle="$vehicle"
                    :colors="$availableColors"
                    :selected-color="$selectedColor"
                />
            </div>
        </div>
    </section>

    <nav class="border-b border-line bg-white" aria-label="{{ __('Nội dung mẫu xe') }}">
        <div class="client-container flex flex-wrap items-center gap-x-6 gap-y-1 py-3 text-sm font-semibold">
            <a class="inline-flex min-h-11 items-center text-ink hover:text-green-text" href="#versions">
                {{ __('Phiên bản & giá') }}
            </a>
            <a class="inline-flex min-h-11 items-center text-ink hover:text-green-text" href="#specifications">
                {{ __('Thông số kỹ thuật') }}
            </a>
            @if($availableColors->isNotEmpty())
                <a class="inline-flex min-h-11 items-center text-ink hover:text-green-text" href="#colors">
                    {{ __('Màu sắc') }}
                </a>
            @endif
            @if($vehicle->brochure_url)
                <a
                    class="inline-flex min-h-11 items-center gap-2 text-green-text sm:ml-auto"
                    href="{{ $vehicle->brochure_url }}"
                    rel="noopener noreferrer"
                >
                    <x-admin.icon name="file" />
                    Xem brochure
                    <x-admin.icon name="external" class="size-4" />
                </a>
            @endif
        </div>
    </nav>

    <div class="client-container py-12 sm:py-16">
        <section id="versions" class="scroll-mt-28" aria-labelledby="versions-title">
            <div class="max-w-2xl">
                <p class="client-eyebrow">{{ __('Lựa chọn của bạn') }}</p>
                <h2 id="versions-title" class="mt-3 font-bold tracking-tight text-balance">
                    {{ __('Phiên bản & giá tham khảo') }}
                </h2>
                <p class="mt-4 leading-7 text-muted">
                    {{ __('Chọn phiên bản phù hợp và nhận báo giá chi tiết từ VinFast Hùng Vương.') }}
                </p>
            </div>
            <div class="mt-7 grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                @forelse($vehicle->variants as $variant)
                    <article class="client-panel flex min-w-0 flex-col p-6 sm:p-7">
                        <div class="flex items-start justify-between gap-4">
                            <h3 class="text-xl font-bold leading-snug">{{ $variant->name }}</h3>
                            <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-paper">
                                <x-admin.icon name="car" class="text-green-text" />
                            </span>
                        </div>
                        <p class="mt-6 text-sm text-muted">{{ __('Giá tham khảo') }}</p>
                        <p class="mt-1 text-3xl font-bold tracking-tight tabular-nums">
                            {{ $variant->price !== null
                                ? number_format((float) $variant->price, 0, ',', '.') : __('Liên hệ') }}
                        </p>
                        @if($variant->price !== null)
                            <p class="mt-1 text-sm text-muted">{{ __('VNĐ') }}</p>
                        @endif
                        <div class="mt-7 border-t border-line pt-5">
                            <a
                                class="client-button-secondary w-full"
                                href="{{ route($clientRoutePrefix.'contact', [
                                    'type' => 'quote', 'vehicle' => $vehicle->id, 'variant' => $variant->id,
                                ]) }}"
                            >
                                {{ __('Nhận báo giá phiên bản này') }}
                                <x-admin.icon name="arrow" class="size-4" />
                            </a>
                        </div>
                    </article>
                @empty
                    <div class="client-panel p-6 md:col-span-2 xl:col-span-3 sm:p-8">
                        <h3 class="text-xl font-bold">{{ __('Cùng tìm phiên bản phù hợp') }}</h3>
                        <p class="mt-3 max-w-2xl leading-7 text-muted">
                            {{ __('Thông tin phiên bản đang được cập nhật. Liên hệ đại lý để nhận giá và '
                                .'ưu đãi hiện hành.') }}
                        </p>
                        <a
                            class="client-button-dark mt-5"

                                href="{{
                                    route($clientRoutePrefix.'contact',
                                     ['type' => 'quote',
                                     'vehicle' => $vehicle->id])
                                }}"
                        >
                            {{ __('Nhận tư vấn') }}
                            <x-admin.icon name="arrow" />
                        </a>
                    </div>
                @endforelse
            </div>
            <p class="mt-5 text-sm leading-6 text-muted">
                {{ __('Giá chỉ mang tính tham khảo. Đại lý xác nhận giá, trang bị và ưu đãi tại thời điểm tư vấn.') }}
            </p>
        </section>

        <section
            id="specifications"
            class="mt-14 scroll-mt-28 border-t border-line pt-12 sm:mt-16 sm:pt-16"
            aria-labelledby="specifications-title"
        >
            <div class="grid gap-7 lg:grid-cols-[minmax(0,0.8fr)_minmax(0,1.2fr)] lg:gap-14">
                <div>
                    <p class="client-eyebrow">{{ __('Tìm hiểu chi tiết') }}</p>
                    <h2
                        id="specifications-title"
                        class="mt-3 font-bold tracking-tight text-balance"
                    >
                        {{ __('Thông số kỹ thuật') }}
                    </h2>
                    <p class="mt-4 max-w-lg leading-7 text-muted">
                        {{
                            __('Thông tin :vehicle giúp bạn cân nhắc trước khi trải nghiệm thực tế.',
                             ['vehicle' => $vehicle->name])
                        }}
                    </p>
                    @if($vehicle->brochure_url)
                        <a
                            class="mt-5 inline-flex min-h-11 items-center gap-2 font-semibold text-green-text"
                            href="{{ $vehicle->brochure_url }}"
                            rel="noopener noreferrer"
                        >
                            {{ __('Xem brochure đầy đủ') }}
                            <x-admin.icon name="external" class="size-4" />
                        </a>
                    @endif
                </div>
                <div class="client-panel min-w-0 overflow-hidden p-0 sm:p-0">
                    @if(count($specifications) > 0)
                        <dl class="divide-y divide-line">
                            @foreach($specifications as $name => $value)
                                <div class="grid gap-2 px-6 py-5 sm:grid-cols-2 sm:gap-5 sm:px-7">
                                    <dt class="text-sm leading-6 text-muted">{{ $name }}</dt>
                                    <dd class="min-w-0 text-base font-semibold leading-6 [overflow-wrap:anywhere]">
                                        {{ is_scalar($value)
                                            ? $value : json_encode($value, JSON_UNESCAPED_UNICODE) }}
                                    </dd>
                                </div>
                            @endforeach
                        </dl>
                    @else
                        <div class="p-6 sm:p-7">
                            <p class="leading-7 text-muted">
                                {{ __('Thông số đang được cập nhật. Đội ngũ tư vấn sẽ cung cấp thông tin về '
                                    .'từng phiên bản.') }}
                            </p>
                            <a
                                class="mt-4 inline-flex min-h-11 items-center gap-2 font-semibold text-green-text"

                                    href="{{
                                        route($clientRoutePrefix.'contact',
                                         ['type' => 'consultation',
                                         'vehicle' => $vehicle->id])
                                    }}"
                            >
                                {{ __('Hỏi về thông số xe') }}
                                <x-admin.icon name="arrow" class="size-4" />
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </section>

        @if($promotions->isNotEmpty())
            <section class="mt-14 sm:mt-16" aria-labelledby="vehicle-promotions-title">
                <p class="client-eyebrow">{{ __('Cơ hội dành cho bạn') }}</p>
                <h2 id="vehicle-promotions-title" class="mt-3 font-bold tracking-tight">
                    {{ __('Ưu đãi cho :vehicle', ['vehicle' => $vehicle->name]) }}
                </h2>
                <div class="mt-7 grid gap-4 md:grid-cols-2">
                    @foreach($promotions as $promotion)
                        <a
                            class="client-panel flex min-h-24 items-center justify-between gap-5 p-6
                                transition-colors hover:border-green-text hover:bg-white"
                            href="{{ route($clientRoutePrefix.'promotions.show', $promotion->slug) }}"
                        >
                            <span class="min-w-0">
                                <span class="block text-sm text-green-text">{{ __('Ưu đãi hiện hành') }}</span>
                                <span class="mt-2 block text-lg leading-7 font-bold">{{ $promotion->title }}</span>
                            </span>
                            <x-admin.icon name="arrow" />
                        </a>
                    @endforeach
                </div>
            </section>
        @endif
    </div>

    <section class="bg-night text-white" aria-labelledby="vehicle-contact-title">
        <div class="client-container grid items-center gap-7 py-10 sm:py-12 lg:grid-cols-[minmax(0,1fr)_auto]">
            <div>
                <p class="client-eyebrow text-brand">{{ __('Trải nghiệm thực tế') }}</p>
                <h2 id="vehicle-contact-title" class="mt-3 font-bold tracking-tight text-balance">
                    {{ __('Cảm nhận :vehicle theo cách của bạn.', ['vehicle' => $vehicle->name]) }}
                </h2>
                <p class="mt-4 max-w-2xl leading-7 text-white/70">
                    {{ __('Đặt lịch lái thử và trao đổi cùng đội ngũ VinFast Hùng Vương về chiếc '
                        .'xe phù hợp với bạn.') }}
                </p>
            </div>
            <a
                class="client-button w-fit"
                data-test-drive-open
                href="{{ route($clientRoutePrefix.'contact', ['type' => 'test_drive', 'vehicle' => $vehicle->id]) }}"
            >
                {{ __('Đặt lịch lái thử') }}
                <x-admin.icon name="arrow" />
            </a>
        </div>
    </section>

    @if($posts->isNotEmpty())
        <section class="client-container py-12 sm:py-16" aria-labelledby="vehicle-posts-title">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="client-eyebrow">{{ __('Góc tư vấn') }}</p>
                    <h2 id="vehicle-posts-title" class="mt-3 font-bold tracking-tight">
                        {{ __('Tìm hiểu thêm về :vehicle', ['vehicle' => $vehicle->name]) }}
                    </h2>
                </div>
                <a
                    class="inline-flex min-h-11 items-center gap-2 font-semibold text-green-text"
                    href="{{ route($clientRoutePrefix.'posts.index') }}"
                >
                    {{ __('Tất cả bài viết') }}
                    <x-admin.icon name="arrow" class="size-4" />
                </a>
            </div>
            <div class="mt-7 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                @foreach($posts as $post)
                    <x-post-card :post="$post" />
                @endforeach
            </div>
        </section>
    @endif
@endsection
