<!doctype html>
<html lang="vi">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex,nofollow">
        <meta name="theme-color" content="#101c20">
        <title>Đăng nhập quản trị · VinFast Hùng Vương</title>
        @vite(['resources/css/app.css', 'resources/js/admin.js'])
        <x-site.typography />
    </head>
    <body class="login-body">
        <main class="login-card">
            <div class="mb-8 flex items-center gap-3">
                <img src="{{ asset('assets/vinfast/logo.png') }}" alt="VinFast" width="44" height="44">
                <span class="text-sm font-bold tracking-wider">HÙNG VƯƠNG
                    <small class="mt-1 text-xs font-normal tracking-normal">VINFAST · QUẢN TRỊ ĐẠI LÝ</small>
                </span>
            </div>
            <p class="eyebrow">Chào mừng trở lại</p>
            <h1>Đăng nhập quản trị</h1>
            <p class="mb-7 text-muted">Truy cập không gian quản lý đại lý.</p>
            <x-feedback :interactive="true" />
            <form method="post" action="{{ route('login.store') }}">
                @csrf
                <x-field name="email" label="Email" type="email" required autocomplete="username" />
                <x-field name="password" label="Mật khẩu" type="password" required autocomplete="current-password" />
                <button>Đăng nhập<x-admin.icon name="arrow" /></button>
            </form>
            <a href="{{ route('home') }}">← Về website</a>
        </main>
    </body>
</html>
