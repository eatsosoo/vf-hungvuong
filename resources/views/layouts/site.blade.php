@php
    $pageSeo = $seo ?? [];
    $siteName = $siteSettings['site_name'] ?? __('VinFast Hùng Vương');
    $pageTitle = $pageSeo['title'] ?? trim($__env->yieldContent('title', $siteName));
    $pageDescription = $pageSeo['description']
        ?? trim($__env->yieldContent('description', $siteSettings['seo_description'] ?? ''));
    $canonicalUrl = $pageSeo['canonical'] ?? trim($__env->yieldContent('canonical', url()->current()));
    $shareImage = $pageSeo['image'] ?? trim($__env->yieldContent('share_image'));
    $navigationItems = [
        ['label' => __('Dòng xe'), 'route' => 'vehicles.index', 'match' => 'vehicles.*'],
        ['label' => __('Ưu đãi'), 'route' => 'promotions.index', 'match' => 'promotions.*'],
        ['label' => __('Góc tư vấn'), 'route' => 'posts.index', 'match' => 'posts.*'],
    ];
@endphp
<!doctype html>
<html lang="{{ app()->getLocale() }}">
    <head>
        @if(! ($preview ?? false))
            <x-site.language-switcher metadata />
        @endif
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#101c20">
        <title>{{ $pageTitle }}</title>
        <meta name="description" content="{{ $pageDescription }}">
        <meta name="robots" content="{{ $pageSeo['robots'] ?? 'index,follow' }}">
        <link rel="canonical" href="{{ $canonicalUrl }}">
        <link rel="sitemap" type="application/xml" href="{{ route('sitemap') }}">
        <meta property="og:title" content="{{ $pageTitle }}">
        <meta property="og:description" content="{{ $pageDescription }}">
        <meta property="og:url" content="{{ $canonicalUrl }}">
        <meta property="og:type" content="{{ $pageSeo['ogType'] ?? 'website' }}">
        <meta property="og:locale" content="{{ app()->getLocale() === 'en' ? 'en_US' : 'vi_VN' }}">
        <meta property="og:site_name" content="{{ $siteName }}">
        <meta name="twitter:card" content="{{ $shareImage ? 'summary_large_image' : 'summary' }}">
        <meta name="twitter:title" content="{{ $pageTitle }}">
        <meta name="twitter:description" content="{{ $pageDescription }}">
        @if($shareImage)
            <meta property="og:image" content="{{ $shareImage }}">
            <meta property="og:image:alt" content="{{ $pageSeo['imageAlt'] ?? $pageTitle }}">
            <meta name="twitter:image" content="{{ $shareImage }}">
            <meta name="twitter:image:alt" content="{{ $pageSeo['imageAlt'] ?? $pageTitle }}">
        @endif
        @if(($pageSeo['ogType'] ?? null) === 'article' && isset($post) && ! ($preview ?? false))
            <meta property="article:published_time" content="{{ $post->published_at->toIso8601String() }}">
            <meta property="article:modified_time" content="{{ $post->updated_at->toIso8601String() }}">
        @endif
        @if(! empty($pageSeo['structuredData']))
            <script type="application/ld+json">
                {!! json_encode(
                    ['@context' => 'https://schema.org', '@graph' => $pageSeo['structuredData']],
                    JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE
                ) !!}
            </script>
        @endif
        @vite(['resources/css/site.css', 'resources/css/test-drive-drawer.css', 'resources/js/site.js'])
        <x-site.typography />
        @vite(['resources/css/language-switcher.css', 'resources/css/site-navbar.css'])
        @yield('head')
    </head>
    <body class="client-shell">
        <a
            class="sr-only fixed top-4 left-4 z-50 rounded-xl bg-brand px-5 py-3 font-bold
                text-night focus:not-sr-only focus:fixed"
            href="#main-content"
        >
            {{ __('Đến nội dung chính') }}
        </a>
        <x-site.navbar :preview="$preview ?? false" />
        <main id="main-content" @hasSection('full_width') @else class="client-container py-12 sm:py-16" @endif>
            @if(session('success') || $errors->any())
                <div class="client-container pt-6"><x-feedback /></div>
            @endif
            @yield('content')
        </main>
        <footer class="client-footer bg-night text-white">
            <div class="client-container pt-12 pb-6 sm:pt-16">
                <div class="grid gap-10 border-b border-white/15 pb-10 md:grid-cols-[1.3fr_1fr_1fr]">
                    <div>
                        <a class="flex items-center gap-3" href="{{ route($clientRoutePrefix.'home') }}">
                            <img class="size-12 object-contain invert" src="{{ asset('assets/vinfast/logo.png') }}"
                                alt="" width="48" height="48" loading="lazy">
                            <span class="text-xl font-bold">{{ $siteName }}</span>
                        </a>
                        <p class="mt-5 max-w-sm text-sm leading-7 text-white/65">
                            {{ __('Cùng bạn lựa chọn chiếc xe điện phù hợp và trải nghiệm hành trình mới.') }}
                        </p>
                        @if(! empty($siteSettings['address']))
                            <address class="mt-4 text-sm leading-7 text-white/65 not-italic">
                                {{ $siteSettings['address'] }}
                            </address>
                        @endif
                        @if(! empty($siteSettings['opening_hours']))
                            <p class="mt-2 text-sm text-white/65">{{ $siteSettings['opening_hours'] }}</p>
                        @endif
                    </div>
                    <nav aria-label="{{ __('Khám phá') }}" class="text-sm">
                        <p class="mb-3 font-bold text-brand">{{ __('Khám phá') }}</p>
                        @foreach($navigationItems as $item)
                            <a class="flex min-h-11 items-center text-white/75 hover:text-brand"
                                href="{{ route($clientRoutePrefix.$item['route']) }}">{{ $item['label'] }}</a>
                        @endforeach
                        <a class="flex min-h-11 items-center text-white/75 hover:text-brand"
                            href="{{ route($clientRoutePrefix.'contact') }}">{{ __('Liên hệ đại lý') }}</a>
                    </nav>
                    <div class="text-sm">
                        <p class="mb-3 font-bold text-brand">{{ __('Kết nối với Hùng Vương') }}</p>
                        @if(! empty($siteSettings['hotline']))
                            <a class="flex min-h-11 items-center text-lg font-bold hover:text-brand"
                                href="tel:{{ $siteSettings['hotline'] }}">{{ $siteSettings['hotline'] }}</a>
                        @endif
                        @if(! empty($siteSettings['zalo_url']))
                            <a class="flex min-h-11 items-center text-white/75 hover:text-brand"

                                    href="{{ $siteSettings['zalo_url'] }}" rel="noopener noreferrer">
                                    {{ __('Tư vấn qua Zalo') }}
                                </a>
                        @endif
                        <a class="client-button mt-4"

                                href="{{ route($clientRoutePrefix.'contact', ['type' => 'quote']) }}">
                                {{ __('Nhận báo giá') }}
                            </a>
                    </div>
                </div>
                <div class="flex flex-wrap items-center justify-between gap-4 pt-5 text-xs text-white/60">
                    <p>© {{ now()->year }} {{ $siteName }}</p>
                    <nav class="flex flex-wrap gap-x-5" aria-label="{{ __('Chính sách') }}">
                        <a class="inline-flex min-h-11 items-center hover:text-white"

                                href="{{ route($clientRoutePrefix.'pages.show', 'faq') }}">
                                {{ __('Câu hỏi thường gặp') }}
                            </a>
                        <a class="inline-flex min-h-11 items-center hover:text-white"
                            href="{{ route($clientRoutePrefix.'pages.show', 'bao-mat') }}">{{ __('Bảo mật') }}</a>
                        <a class="inline-flex min-h-11 items-center hover:text-white"
                            href="{{ route($clientRoutePrefix.'pages.show', 'dieu-khoan') }}">{{ __('Điều khoản') }}</a>
                    </nav>
                </div>
            </div>
        </footer>
        <x-site.test-drive-drawer :vehicles="$testDriveVehicles"
            :context-vehicle-id="request()->routeIs('vehicles.show', 'en.vehicles.show')
                ? ($vehicle->id ?? null) : null" />
    </body>
</html>
