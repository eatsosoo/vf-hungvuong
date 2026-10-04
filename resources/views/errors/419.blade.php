<!doctype html>
<html lang="{{ app()->getLocale() }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex,nofollow">
        <title>
            {{ __('Phiên đã hết hạn') }} · VinFast Hùng Vương
        </title>
        <link rel="stylesheet" href="{{ asset('css/studio.css') }}">
        <x-site.typography />
    </head>
    <body class="login-body">
        <main class="login-card">
            <p class="eyebrow">
                VINFAST HÙNG VƯƠNG · 419
            </p>
            <h1>
                {{ __('Phiên đã hết hạn') }}
            </h1>
            <p>
                {{ __('Vui lòng tải lại trang và gửi lại biểu mẫu.') }}
            </p>
            <a class="button" href="{{ route(app()->getLocale() === 'en' ? 'en.home' : 'home') }}">
                {{ __('Về trang chủ') }}
            </a>
        </main>
    </body>
</html>
