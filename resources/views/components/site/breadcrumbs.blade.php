@props(['items', 'dark' => false])
<nav aria-label="{{ __('Đường dẫn trang') }}">
    <ol class="flex flex-wrap items-center gap-x-2 gap-y-1 text-xs leading-6 sm:text-sm
        {{ $dark ? 'text-white/60' : 'text-muted' }}">
        @foreach($items as $item)
            <li class="inline-flex items-center gap-2">
                @unless($loop->first)
                    <svg class="size-3 shrink-0" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                        <path d="m6 4 4 4-4 4" stroke="currentColor" stroke-width="1.5" />
                    </svg>
                @endunless
                @if(isset($item['url']))
                    <a class="inline-flex min-h-11 items-center hover:underline underline-offset-4"
                        href="{{ $item['url'] }}">{{ $item['label'] }}</a>
                @else
                    <span aria-current="page" class="{{ $dark ? 'text-white/85' : 'text-ink' }}">
                        {{ $item['label'] }}
                    </span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
