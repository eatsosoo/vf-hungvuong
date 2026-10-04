@extends('layouts.site')
@section('full_width', 'true')
@section('content')
    <article>
        <header class="border-b border-line bg-white">
            <div class="client-container pt-5 pb-10 sm:pt-7 sm:pb-12">
                <x-site.breadcrumbs :items="$breadcrumbs" />
                @if($preview)
                    <div class="notice mt-5" role="status">
                        {{ __('Xem trước bản đã lưu · Nội dung này có thể chưa công khai.') }}
                    </div>
                @endif
                <div class="mx-auto mt-6 max-w-4xl sm:mt-8">
                    @if($post->category)
                        <a class="client-eyebrow inline-flex min-h-11 items-center hover:underline"
                            href="{{ route($clientRoutePrefix.'posts.index', ['category' => $post->category->slug]) }}">
                            {{ $post->category->name }}
                        </a>
                    @else
                        <p class="client-eyebrow">{{ __('Góc tư vấn xe điện') }}</p>
                    @endif
                    <h1 class="mt-3">{{ $post->title }}</h1>
                    @if($post->excerpt)
                        <p class="mt-6 text-base leading-7 text-muted">{{ $post->excerpt }}</p>
                    @endif
                    <div class="mt-7 flex flex-wrap items-center gap-x-5 gap-y-2 text-sm text-muted">
                        <span class="font-semibold text-ink">{{ $post->author->name }}</span>
                        @if($post->published_at)
                            <time datetime="{{ $post->published_at->toIso8601String() }}">
                                Đăng ngày {{ $post->published_at->format('d/m/Y') }}
                            </time>
                        @endif
                        <span>{{ $readingMinutes }} {{ __('phút đọc') }}</span>
                        @if($post->published_at && ! $post->updated_at->isSameDay($post->published_at))
                            <time datetime="{{ $post->updated_at->toIso8601String() }}">
                                Cập nhật {{ $post->updated_at->format('d/m/Y') }}
                            </time>
                        @endif
                    </div>
                </div>
                @if($post->media)
                    <figure class="mx-auto mt-8 max-w-5xl overflow-hidden rounded-3xl bg-paper sm:mt-10">
                        <img
                            class="max-h-[620px] w-full object-cover"
                            src="{{ $post->media->url() }}"
                            alt="{{ $post->media->alt ?: $post->title }}"
                            width="{{ $post->media->width ?: 1200 }}"
                            height="{{ $post->media->height ?: 750 }}"
                            fetchpriority="high"
                            decoding="async"
                        >
                    </figure>
                @endif
            </div>
        </header>
        <div class="client-container py-10 sm:py-14">
            <div class="grid items-start gap-10 lg:grid-cols-[minmax(0,1fr)_19rem] lg:gap-14">
                <div class="min-w-0 rounded-2xl bg-white p-6 sm:p-8 lg:p-10">
                    <div class="client-prose">
                        {!! $content['html'] !!}
                    </div>
                    @if($post->tags->isNotEmpty())
                        <nav class="mt-10 flex flex-wrap gap-2 border-t border-line pt-6"
                            aria-label="{{ __('Thẻ bài viết') }}">
                            @foreach($post->tags as $tag)
                                <a class="inline-flex min-h-11 items-center rounded-full bg-paper px-4 text-sm
                                    font-semibold text-green-text hover:bg-brand/25"

                                        href="{{
                                            route($clientRoutePrefix.'posts.index',
                                             ['tag' => $tag->slug])
                                        }}">#{{
                                            $tag->name
                                        }}
                                    </a>
                            @endforeach
                        </nav>
                    @endif
                    <a class="mt-6 inline-flex min-h-11 items-center gap-2 text-sm font-bold text-green-text"
                        href="{{ route($clientRoutePrefix.'posts.index') }}">
                        <x-admin.icon name="arrow" class="size-4 rotate-180" />
                        {{ __('Về góc tư vấn') }}
                    </a>
                </div>
                <aside class="order-first space-y-6 lg:sticky lg:top-8 lg:order-last"
                    aria-label="{{ __('Hỗ trợ đọc bài') }}">
                    @if($content['headings'])
                        <nav class="client-panel p-5 sm:p-6" aria-label="{{ __('Mục lục') }}">
                            <details open>
                                <summary class="min-h-11 cursor-pointer text-base font-bold text-ink">
                                    {{ __('Trong bài viết này') }}
                                </summary>
                                <ol class="mt-3 space-y-1 border-l border-line">
                                    @foreach($content['headings'] as $heading)
                                        <li>
                                            <a class="flex min-h-11 items-center border-l-2 border-transparent
                                                py-2 pl-4 text-sm leading-6 text-muted transition-colors
                                                hover:border-green-text hover:text-green-text"
                                                href="#{{ $heading['id'] }}">{{ $heading['text'] }}</a>
                                        </li>
                                    @endforeach
                                </ol>
                            </details>
                        </nav>
                    @endif
                    <div class="hidden rounded-2xl bg-night p-6 text-white lg:block">
                        <p class="client-eyebrow text-brand">{{ __('Bạn cần tư vấn thêm?') }}</p>
                        <h2 class="mt-4 text-xl sm:text-xl">{{ __('Trao đổi cùng Hùng Vương.') }}</h2>
                        <p class="mt-3 text-sm leading-7 text-white/70">
                            {{ __('Đội ngũ đại lý sẽ giúp bạn tìm hiểu mẫu xe và lựa chọn phiên bản phù hợp.') }}
                        </p>
                        <a class="client-button mt-5 w-full" href="{{ route($clientRoutePrefix.'contact') }}">
                            {{ __('Nhận tư vấn') }} <x-admin.icon name="arrow" class="size-4" />
                        </a>
                    </div>
                </aside>
            </div>
        </div>
    </article>
    <div class="client-container pb-12 sm:pb-16">
        <x-site.cta title="{{ __('Tìm hiểu hôm nay. Trải nghiệm thực tế.') }}" />
        @if($vehicles->isNotEmpty())
            <section class="mt-12 sm:mt-16" aria-labelledby="related-vehicles-title">
                <p class="client-eyebrow">{{ __('Từ bài viết đến trải nghiệm') }}</p>
                <h2 id="related-vehicles-title" class="mt-3">{{ __('Mẫu xe trong bài viết') }}</h2>
                <div class="mt-7 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($vehicles as $vehicle)
                        <x-vehicle-card :vehicle="$vehicle" />
                    @endforeach
                </div>
            </section>
        @endif
        @if($related->isNotEmpty())
            <section class="mt-12 sm:mt-16" aria-labelledby="related-posts-title">
                <div class="flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <p class="client-eyebrow">{{ __('Tiếp tục khám phá') }}</p>
                        <h2 id="related-posts-title" class="mt-3">{{ __('Có thể bạn quan tâm') }}</h2>
                    </div>
                    <a class="inline-flex min-h-11 items-center gap-2 text-sm font-bold text-green-text"
                        href="{{ route($clientRoutePrefix.'posts.index') }}">
                        {{ __('Tất cả bài viết') }} <x-admin.icon name="arrow" class="size-4" />
                    </a>
                </div>
                <div class="mt-7 grid gap-6 sm:grid-cols-2">
                    @foreach($related as $item)
                        <x-post-card :post="$item" />
                    @endforeach
                </div>
            </section>
        @endif
    </div>
@endsection
