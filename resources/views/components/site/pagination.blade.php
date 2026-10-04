@props(['paginator'])
@if($paginator->hasPages())
    @php
        $pages = collect([1, $paginator->lastPage()])
            ->merge(range(max(1, $paginator->currentPage() - 1),
                min($paginator->lastPage(), $paginator->currentPage() + 1)))
            ->unique()->sort()->values();
    @endphp
    <nav class="mt-10 flex flex-wrap items-center justify-center gap-2" aria-label="{{ __('Phân trang bài viết') }}">
        @if($paginator->previousPageUrl())
            <a class="client-button secondary px-4" href="{{ $paginator->previousPageUrl() }}" rel="prev">
                {{ __('Trang trước') }}
            </a>
        @endif
        @foreach($pages as $page)
            @if(! $loop->first && $page - $pages[$loop->index - 1] > 1)
                <span class="px-1 text-muted" aria-hidden="true">…</span>
            @endif
            @if($page === $paginator->currentPage())
                <span class="flex size-12 items-center justify-center rounded-full bg-night font-bold text-white"
                    aria-current="page" aria-label="{{ __('Trang :page', ['page' => $page]) }}">{{ $page }}</span>
            @else
                <a class="flex size-12 items-center justify-center rounded-full border border-line bg-white
                    font-semibold text-ink hover:border-green-text hover:bg-brand/15"

                        href="{{ $paginator->url($page) }}"
                        aria-label="{{ __('Trang :page', ['page' => $page]) }}">
                        {{ $page }}
                    </a>
            @endif
        @endforeach
        @if($paginator->nextPageUrl())
            <a class="client-button secondary px-4" href="{{ $paginator->nextPageUrl() }}" rel="next">
                {{ __('Trang tiếp') }}
            </a>
        @endif
    </nav>
@endif
