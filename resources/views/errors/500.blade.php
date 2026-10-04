<!doctype html>
<html lang="{{ app()->getLocale() }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex,nofollow">
        <title>
            {{ __('Chưa thể xử lý yêu cầu') }} · VinFast Hùng Vương
        </title>
        <link rel="stylesheet" href="{{ asset('css/studio.css') }}">
        <x-site.typography />
    </head>
    <body class="login-body">
        <main class="login-card">
            <p class="eyebrow">
                VINFAST HÙNG VƯƠNG · 500
            </p>
            <h1>
                {{ __('Chưa thể xử lý yêu cầu') }}
            </h1>
            <p>
                {{ __('Vui lòng thử lại sau. Đại lý sẽ kiểm tra sự cố.') }}
            </p>
            <a class="button" href="{{ route(app()->getLocale() === 'en' ? 'en.home' : 'home') }}">
                {{ __('Về trang chủ') }}
            </a>
        </main>
    </body>
</html>
