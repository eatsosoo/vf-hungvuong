<!doctype html>
<html lang="vi">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex,nofollow">
        <meta name="theme-color" content="#101c20">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>@yield('title', 'Quản trị') · VinFast Hùng Vương</title>
        @vite(['resources/css/app.css', 'resources/js/admin.js'])
        <x-site.typography />
        @stack('assets')
    </head>
    <body class="admin-shell">
        <a href="#admin-content"
            class="sr-only focus:not-sr-only focus:fixed focus:top-4 focus:left-4 focus:z-50 focus:bg-brand focus:p-4">
            Đến nội dung chính
        </a>
        <aside class="sidebar">
            <div class="flex items-center justify-between gap-3">
                <a class="studio-brand" href="{{ route('admin.dashboard') }}" aria-label="Quản trị VinFast Hùng Vương">
                    <img class="brand-logo" src="{{ asset('assets/vinfast/logo.png') }}" alt="" width="44" height="44">
                    <span class="sidebar-brand-label">HÙNG VƯƠNG<small>VINFAST · QUẢN TRỊ ĐẠI LÝ</small></span>
                </a>
                <button type="button" class="menu-toggle" aria-expanded="false"
                    aria-controls="admin-navigation" aria-label="Mở menu quản trị" data-admin-menu>
                    <x-admin.icon name="menu" />
                </button>
            </div>
            <div id="admin-navigation" class="mt-6 lg:block" data-admin-navigation>
                <nav aria-label="Quản trị">
                    <x-admin.nav-link :href="route('admin.dashboard')"
                        :active="request()->routeIs('admin.dashboard')" icon="dashboard">Tổng quan</x-admin.nav-link>
                    @can('manage-catalog')
                        <p class="nav-group">Đại lý & sản phẩm</p>
                        <x-admin.nav-link :href="route('admin.vehicles.index')"
                            :active="request()->routeIs('admin.vehicles.*')" icon="car">Danh mục xe</x-admin.nav-link>
                        <x-admin.nav-link :href="route('admin.promotions.index')"
                            :active="request()->routeIs('admin.promotions.*')" icon="tag">Khuyến mãi</x-admin.nav-link>
                        <x-admin.nav-link :href="route('admin.pages.index')"
                            :active="request()->routeIs('admin.pages.*')" icon="file">Trang nội dung</x-admin.nav-link>
                    @endcan
                    @can('viewAny', App\Models\Post::class)
                        <p class="nav-group">Nội dung website</p>
                        <x-admin.nav-link :href="route('admin.posts.index')"
                            :active="request()->routeIs('admin.posts.*')" icon="file">Bài viết SEO</x-admin.nav-link>
                        <x-admin.nav-link :href="route('admin.taxonomies.index', 'categories')" icon="folder"
                            :active="request()->routeIs('admin.taxonomies.*')
                                && request()->route('kind') === 'categories'">
                            Danh mục bài viết
                        </x-admin.nav-link>
                        <x-admin.nav-link :href="route('admin.taxonomies.index', 'tags')" icon="tag"
                            :active="request()->routeIs('admin.taxonomies.*') && request()->route('kind') === 'tags'">
                            Thẻ nội dung
                        </x-admin.nav-link>
                        <x-admin.nav-link :href="route('admin.media.index')"
                            :active="request()->routeIs('admin.media.*')" icon="image">Thư viện ảnh</x-admin.nav-link>
                    @endcan
                    @can('viewAny', App\Models\Lead::class)
                        <p class="nav-group">Chăm sóc khách hàng</p>
                        <x-admin.nav-link :href="route('admin.leads.index')" icon="users"
                            :active="request()->routeIs('admin.leads.*') && ! request('type')">
                            Khách hàng & yêu cầu
                        </x-admin.nav-link>
                        <x-admin.nav-link :href="route('admin.leads.index', ['type' => 'test_drive'])" icon="calendar"
                            :active="request()->routeIs('admin.leads.index') && request('type') === 'test_drive'">
                            Lịch lái thử
                        </x-admin.nav-link>
                        <x-admin.nav-link :href="route('admin.leads.index', ['type' => 'quote'])" icon="file"
                            :active="request()->routeIs('admin.leads.index') && request('type') === 'quote'">
                            Yêu cầu báo giá
                        </x-admin.nav-link>
                    @endcan
                    @can('view-reports')
                        <p class="nav-group">Hiệu quả hoạt động</p>
                        <x-admin.nav-link :href="route('admin.reports.index')"
                            :active="request()->routeIs('admin.reports.*')" icon="chart">Báo cáo</x-admin.nav-link>
                        <x-admin.nav-link :href="route('admin.audit.index')" icon="history"
                            :active="request()->routeIs('admin.audit.*')">Nhật ký thao tác</x-admin.nav-link>
                    @endcan
                    <p class="nav-group">Hệ thống</p>
                    @can('manage-users')
                        <x-admin.nav-link :href="route('admin.users.index')" icon="users"
                            :active="request()->routeIs('admin.users.*')">Tài khoản & quyền</x-admin.nav-link>
                    @endcan
                    @can('manage-settings')
                        <x-admin.nav-link :href="route('admin.settings.edit')" icon="settings"
                            :active="request()->routeIs('admin.settings.*')">Cấu hình website</x-admin.nav-link>
                    @endcan
                    <x-admin.nav-link :href="route('admin.password.edit')" icon="lock"
                        :active="request()->routeIs('admin.password.*')">Đổi mật khẩu</x-admin.nav-link>
                    <x-admin.nav-link :href="route('home')" icon="external">Xem website</x-admin.nav-link>
                </nav>
                <form class="mt-6 border-t border-white/15 pt-5" method="post" action="{{ route('admin.logout') }}">
                    @csrf
                    <button class="secondary sidebar-logout" aria-label="Đăng xuất" title="Đăng xuất">
                        <x-admin.icon name="logout" /><span class="nav-label">Đăng xuất</span>
                    </button>
                </form>
            </div>
        </aside>
        <main class="admin-main" id="admin-content" tabindex="-1">
            <header class="admin-top">
                <div class="flex items-center gap-3 text-sm text-muted">
                    <button type="button" class="sidebar-toggle hidden lg:inline-flex"
                        data-sidebar-toggle aria-expanded="true" aria-controls="admin-navigation"
                        aria-label="Thu gọn sidebar" title="Thu gọn sidebar">
                        <x-admin.icon name="panel" />
                    </button>
                    <span>
                        Quản trị <span class="mx-2 text-line" aria-hidden="true">/</span>
                        <span class="font-semibold text-ink">@yield('title', 'Tổng quan')</span>
                    </span>
                </div>
                <div class="flex min-w-0 items-center gap-3">
                    <span class="grid size-10 shrink-0 place-items-center rounded-full bg-paper text-green-text">
                        <x-admin.icon name="users" />
                    </span>
                    <div class="min-w-0 text-sm">
                        <strong class="block break-words">{{ auth()->user()->name }}</strong>
                        <small>{{ __('studio.role.'.auth()->user()->role->value) }}</small>
                    </div>
                </div>
            </header>
            <div class="admin-content">
                <x-feedback :interactive="true" />
                @yield('content')
                <footer class="mt-12 border-t border-line pt-5 text-xs text-muted">
                    © {{ now()->year }} VinFast Hùng Vương · Không gian quản trị đại lý
                </footer>
            </div>
        </main>
        @can('manage-content')
            <x-admin.media-library-modal />
        @endcan
    </body>
</html>
