@props(['post', 'featured' => false])
<article class="group flex min-w-0 flex-col overflow-hidden rounded-2xl border border-line bg-white
    {{ $featured ? 'md:col-span-2 md:grid md:grid-cols-2' : '' }}">
    <a class="block overflow-hidden bg-[#e9eee9]" href="{{ route($clientRoutePrefix.'posts.show', $post->slug) }}"
        aria-label="{{ __('Đọc bài: :title', ['title' => $post->title]) }}" tabindex="-1">
        @if($post->media)
            <img
                class="aspect-[16/10] h-full w-full object-cover"
                src="{{ $post->media->url() }}"
                alt="{{ $post->media->alt ?: $post->title }}"
                width="{{ $post->media->width ?: 960 }}"
                height="{{ $post->media->height ?: 600 }}"
                loading="lazy"
                decoding="async"
            >
        @else
            <div class="flex aspect-[16/10] h-full items-center justify-center gap-3 text-green-text">
                <x-admin.icon name="file" class="size-10 opacity-60" />
                <span class="text-sm font-semibold">{{ __('Góc tư vấn xe điện') }}</span>
            </div>
        @endif
    </a>
    <div class="flex flex-1 flex-col p-6 {{ $featured ? 'lg:p-8' : '' }}">
        <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-muted">
            <span class="font-bold text-green-text">{{ $post->category?->name ?: __('Góc tư vấn') }}</span>
            @if($post->published_at)
                <span aria-hidden="true">·</span>
                <time datetime="{{ $post->published_at->toIso8601String() }}">
                    {{ $post->published_at->format('d/m/Y') }}
                </time>
            @endif
        </div>
        <h3 class="mt-3 {{ $featured ? 'text-2xl lg:text-3xl' : 'text-xl' }}">
            <a class="transition-colors group-hover:text-green-text" href="{{ route($clientRoutePrefix.'posts.show', $post->slug) }}">
                {{ $post->title }}
            </a>
        </h3>
        @if($post->excerpt)
            <p class="mt-3 text-sm leading-7 text-muted">
                {{ Str::limit($post->excerpt, $featured ? 220 : 150) }}
            </p>
        @endif
        <a class="mt-auto flex min-h-11 w-fit items-center gap-2 pt-5 text-sm font-bold text-green-text"
            href="{{ route($clientRoutePrefix.'posts.show', $post->slug) }}" aria-label="{{ __('Đọc tiếp: :title', ['title' => $post->title]) }}">
            {{ __('Đọc bài viết') }} <x-admin.icon name="arrow" class="size-4" />
        </a>
    </div>
</article>
