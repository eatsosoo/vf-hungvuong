@extends('layouts.site')
@section('full_width', 'true')
@section('content')
    <section class="bg-night text-white" aria-labelledby="posts-title">
        <div class="client-container pt-5 pb-12 sm:pt-7 sm:pb-16">
            <x-site.breadcrumbs :items="$breadcrumbs" dark />
            <div class="mt-6 grid gap-7 lg:grid-cols-[1.2fr_0.8fr] lg:items-end">
                <div>
                    <p class="client-eyebrow text-brand">{{ __('Góc tư vấn xe điện') }}</p>
                    <h1 id="posts-title" class="mt-4">{{ __('Hiểu xe.') }}<br>{{ __('Chọn đúng.') }}</h1>
                </div>
                <p class="max-w-lg text-lg leading-8 text-white/70">
                    {{ __('Từ lựa chọn chiếc xe phù hợp đến trải nghiệm mỗi ngày. Khám phá kiến '
                        .'thức và kinh nghiệm về xe điện cùng VinFast Hùng Vương.') }}
                </p>
            </div>
        </div>
    </section>
    <div class="client-container py-10 sm:py-14">
        @if($categories->isNotEmpty())
            <nav class="mb-7 flex flex-wrap gap-2" aria-label="{{ __('Danh mục bài viết') }}">
                <a class="client-button {{ ! request('category') ? 'dark' : 'secondary' }} px-5"
                    href="{{ route($clientRoutePrefix.'posts.index', request()->only('q', 'tag')) }}"
                    @if(! request('category')) aria-current="page" @endif>{{ __('Tất cả chủ đề') }}</a>
                @foreach($categories as $category)
                    <a class="client-button {{ request('category') === $category->slug ? 'dark' : 'secondary' }} px-5"

                            href="{{
                                route($clientRoutePrefix.'posts.index',
                                 request()->only('q',
                                 'tag') + ['category' => $category->slug])
                            }}"
                        @if(request('category') === $category->slug) aria-current="page" @endif>
                        {{ $category->name }}
                        <span class="font-normal opacity-65">{{ $category->posts_count }}</span>
                    </a>
                @endforeach
            </nav>
        @endif
        <form class="client-panel grid gap-x-5 gap-y-2 sm:grid-cols-2 lg:grid-cols-[1.5fr_1fr_1fr_auto] lg:items-end"
            action="{{ route($clientRoutePrefix.'posts.index') }}" method="get"
                role="search"
                aria-label="{{ __('Tìm bài viết') }}">
            <x-field name="q"
                label="{{ __('Tìm bài viết') }}" :value="$searchQuery"
                placeholder="{{ __('Bạn muốn tìm hiểu điều gì?') }}" />
            <x-select name="category" label="{{ __('Chủ đề') }}">
                <option value="">{{ __('Tất cả chủ đề') }}</option>
                @foreach($categories as $category)
                    <option value="{{ $category->slug }}" @selected(request('category') === $category->slug)>
                        {{ $category->name }}
                    </option>
                @endforeach
            </x-select>
            <x-select name="tag" label="{{ __('Thẻ bài viết') }}">
                <option value="">{{ __('Tất cả thẻ') }}</option>
                @foreach($tags as $tag)
                    <option value="{{ $tag->slug }}" @selected(request('tag') === $tag->slug)>
                        {{ $tag->name }}
                    </option>
                @endforeach
            </x-select>
            <button class="client-button mb-5" type="submit">
                {{ __('Tìm kiếm') }} <x-admin.icon name="arrow" class="size-4" />
            </button>
        </form>
        <section class="mt-10 sm:mt-12" aria-labelledby="articles-heading">
            <div class="mb-7 flex flex-wrap items-end justify-between gap-3">
                <div>
                    <p class="client-eyebrow">{{ __('Khám phá & trải nghiệm') }}</p>
                    <h2 id="articles-heading" class="mt-3">
                        {{ $activeCategory?->name ?: ($activeTag?->name ?: __('Bài viết mới nhất')) }}
                    </h2>
                    @if($searchQuery !== '')
                        <p class="mt-3 text-muted">{{ __('Kết quả cho “') }}{{ $searchQuery }}”</p>
                    @endif
                </div>
                <p class="text-sm text-muted">{{ number_format($posts->total(), 0, ',', '.') }} {{ __('bài viết') }}</p>
            </div>
            @if($posts->isNotEmpty())
                <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    @foreach($posts as $post)
                        <x-post-card :post="$post" :featured="$loop->first" />
                    @endforeach
                </div>
                <x-site.pagination :paginator="$posts" />
            @else
                <x-site.empty-state title="{{ __('Chưa có bài viết phù hợp.') }}"
                    description="{{ __('Thử từ khóa khác hoặc xem tất cả chủ đề để tiếp tục khám phá.') }}">
                    @if(request()->hasAny(['q', 'category', 'tag']))
                        <a class="client-button secondary mt-6" href="{{ route($clientRoutePrefix.'posts.index') }}">
                            {{ __('Xem tất cả bài viết') }}
                        </a>
                    @endif
                </x-site.empty-state>
            @endif
        </section>
        <div class="mt-12 sm:mt-16"><x-site.cta /></div>
    </div>
@endsection
