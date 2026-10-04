@props(['metadata' => false])
@php
    $currentRoute = request()->route();
    $routeName = Illuminate\Support\Str::after($currentRoute->getName(), 'en.');
    $parameters = array_merge(request()->except('locale'), $currentRoute->parameters());
    $languageUrls = [
        'vi' => route($routeName, $parameters),
        'en' => route('en.'.$routeName, $parameters),
    ];
@endphp
@if($metadata)
    @foreach($languageUrls as $locale => $url)
        <link rel="alternate" hreflang="{{ $locale }}" href="{{ $url }}">
    @endforeach
@else
    <details class="language-switcher" data-language-switcher>
        <summary aria-label="{{ __('Ngôn ngữ') }}: {{ app()->getLocale() === 'en' ? 'English' : 'Tiếng Việt' }}">
            <img src="{{ asset('assets/flags/'.app()->getLocale().'.svg') }}" alt="" width="24" height="16">
            <svg viewBox="0 0 12 12" width="10" height="10" aria-hidden="true" fill="none">
                <path d="m3 4.5 3 3 3-3" stroke="currentColor" stroke-width="1.5"
                    stroke-linecap="round" stroke-linejoin="round" />
            </svg>
        </summary>
        <nav class="language-switcher-options" aria-label="{{ __('Ngôn ngữ') }}">
            @foreach($languageUrls as $locale => $url)
                <a href="{{ $url }}" lang="{{ $locale }}" hreflang="{{ $locale }}"
                    @if(app()->getLocale() === $locale) aria-current="true" @endif>
                    <img src="{{ asset('assets/flags/'.$locale.'.svg') }}" alt="" width="24" height="16">
                    <span>{{ $locale === 'vi' ? 'Tiếng Việt' : 'English' }}</span>
                    @if(app()->getLocale() === $locale)
                        <x-admin.icon name="check" class="language-switcher-check" />
                    @endif
                </a>
            @endforeach
        </nav>
    </details>
@endif
