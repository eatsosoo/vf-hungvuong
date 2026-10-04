@props(['home' => false, 'preview' => false])
@php
    $homeUrl = route($clientRoutePrefix.'home');
    $navigation = [
        ['label' => __('Trang chủ'), 'url' => $home ? '#top' : $homeUrl,
            'active' => request()->routeIs('home', 'en.home')],
        ['label' => __('Dòng xe'), 'url' => $home ? '#models' : route($clientRoutePrefix.'vehicles.index'),
            'active' => request()->routeIs('vehicles.*', 'en.vehicles.*')],
        ['label' => __('Trải nghiệm'), 'url' => $home ? '#experience' : $homeUrl.'#experience', 'active' => false],
        ['label' => __('Góc tư vấn'), 'url' => route($clientRoutePrefix.'posts.index'),
            'active' => request()->routeIs('posts.*', 'en.posts.*')],
        ['label' => __('Ưu đãi'), 'url' => route($clientRoutePrefix.'promotions.index'),
            'active' => request()->routeIs('promotions.*', 'en.promotions.*')],
    ];
    $testDriveUrl = route($clientRoutePrefix.'contact', ['type' => 'test_drive']);
@endphp
<header @class(['site-navbar-surface', 'site-navbar-home' => $home])>
    <div class="site-navbar" data-site-navbar>
        <a class="site-navbar-brand" href="{{ $home ? '#top' : $homeUrl }}"
            aria-label="{{ __('VinFast Hùng Vương · Trang chủ') }}">
            <img src="{{ asset('assets/vinfast/logo.png') }}" alt="" width="46" height="46">
            <span>
                {{ __('HÙNG VƯƠNG') }}
                <small>{{ __('VINFAST · ĐẠI LÝ XE ĐIỆN') }}</small>
            </span>
        </a>
        <nav id="site-navigation" class="site-navbar-menu" data-site-navigation
            aria-label="{{ __('Điều hướng chính') }}">
            @foreach($navigation as $item)
                <a href="{{ $item['url'] }}" @if($item['active']) aria-current="page" @endif>
                    {{ $item['label'] }}
                </a>
            @endforeach
            <a class="site-navbar-mobile-cta" data-test-drive-open href="{{ $testDriveUrl }}">
                {{ __('Đăng ký lái thử') }}
            </a>
        </nav>
        <div class="site-navbar-actions">
            <a class="site-navbar-cta" data-test-drive-open href="{{ $testDriveUrl }}">
                {{ __('Đăng ký lái thử') }}
                <x-admin.icon name="arrow" />
            </a>
            <button class="site-navbar-toggle" type="button" data-site-menu
                aria-expanded="false" aria-controls="site-navigation" aria-label="{{ __('Mở hoặc đóng menu') }}">
                <x-admin.icon name="menu" />
            </button>
            @if(! $preview)
                <x-site.language-switcher />
            @endif
        </div>
    </div>
</header>
