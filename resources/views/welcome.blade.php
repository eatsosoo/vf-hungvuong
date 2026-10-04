<!doctype html>
<html lang="{{ app()->getLocale() }}">
    <head>
        <x-site.language-switcher metadata />
        <meta charset="utf-8">
        <link rel="sitemap" type="application/xml" href="{{ route('sitemap') }}">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#101c20">
        <title>
            {{ $siteSettings['site_name'] ?? __('VinFast Hùng Vương') }}
        </title>
        <meta
            name="description"
            content="{{ $siteSettings['seo_description'] ?? __('Khám phá dòng xe điện VinFast tại Hùng Vương.') }}"
        >
        <link rel="canonical" href="{{ route($clientRoutePrefix.'home') }}">
        <link rel="stylesheet" href="{{ asset('css/vinfast.css') }}">
        @vite([
            'resources/css/select.css',
            'resources/css/test-drive-drawer.css',
            'resources/js/test-drive-drawer.js',
        ])
        <x-site.typography />
        @vite(['resources/css/language-switcher.css', 'resources/css/site-navbar.css', 'resources/js/site-navbar.js'])
    </head>
    <body>
        <svg class="icon-definitions" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <symbol id="arrow" viewBox="0 0 24 24">
                <path d="M5 12h14m-6-6 6 6-6 6"/>
            </symbol>
            <symbol id="shield" viewBox="0 0 24 24">
                <path d="m12 3 8 3v6c0 5-8 9-8 9s-8-4-8-9V6l8-3Z"/>
                <path d="m8 12 3 3 5-6"/>
            </symbol>
            <symbol id="calendar" viewBox="0 0 24 24">
                <rect x="4" y="5" width="16" height="16" rx="3"/>
                <path d="M8 3v4m8-4v4M4 10h16m-12 5 3 3 5-5"/>
            </symbol>
            <symbol id="support" viewBox="0 0 24 24">
                <path d="M4 13v-2a8 8 0 0 1 16 0v2M20 17v2l-8 2"/>
                <rect x="3" y="11" width="4" height="7" rx="2"/>
                <rect x="17" y="11" width="4" height="7" rx="2"/>
            </symbol>
            <symbol id="pin" viewBox="0 0 24 24">
                <path d="M20 10c0 6-8 11-8 11S4 16 4 10a8 8 0 0 1 16 0Z"/>
                <circle cx="12" cy="10" r="3"/>
            </symbol>
            <symbol id="electric" viewBox="0 0 24 24">
                <path d="m13 2-9 12h7l-1 8 10-13h-8l1-7Z"/>
            </symbol>
        </svg>
        <main id="top">
            <section class="hero" aria-labelledby="heroTitle">
                <x-site.navbar home />
                <div class="hero-content container">
                    <div class="hero-copy">
                        <p class="eyebrow">
                            {{ __('KHỞI ĐẦU HÀNH TRÌNH MỚI') }}
                        </p>
                        <h1 id="heroTitle">
                            {{ $siteSettings['hero_title'] ?? __('Xe điện tiên phong. Cho mọi hành trình.') }}
                        </h1>
                        <p>
                            {{ $siteSettings['hero_description'] ?? __('VinFast Hùng Vương đồng hành cùng bạn.') }}
                        </p>
                        <div class="hero-actions">
                            <a class="button button-primary" data-test-drive-open
                                href="{{ route($clientRoutePrefix.'contact', ['type' => 'test_drive']) }}">
                                <span class="button-dot">
                                    ↗
                                </span>
                                {{ __('Đăng ký lái thử') }}
                            </a>
                            <a class="button button-outline" href="#models">
                                {{ __('Khám phá dòng xe') }}
                            </a>
                        </div>
                    </div>
                    <img
                        class="hero-car"
                        src="{{ $banner?->url() ?? asset('assets/vinfast/vinfast-vf7-white.png') }}"
                        alt="{{ $banner?->alt ?? __('VinFast VF 7 màu trắng') }}"
                        fetchpriority="high"
                    >
                    <span class="hero-car-label">
                        VINFAST VF 7
                        <span>
                            {{ __('THIẾT KẾ BỨT PHÁ') }}
                        </span>
                    </span>
                </div>
                <div class="hero-benefits container">
                    <div>
                        <span class="icon-circle">
                            <svg class="icon">
                                <use href="#support"/>
                            </svg>
                        </span>
                        <p>
                            {{ __('Tư vấn tận tâm') }}
                            <small>
                                {{ __('Đồng hành cùng bạn') }}
                                <br>
                                {{ __('trên mọi hành trình') }}
                            </small>
                        </p>
                    </div>
                    <div>
                        <span class="icon-circle">
                            <svg class="icon">
                                <use href="#calendar"/>
                            </svg>
                        </span>
                        <p>
                            {{ __('Lái thử linh hoạt') }}
                            <small>
                                {{ __('Chọn thời gian') }}
                                <br>
                                {{ __('phù hợp với bạn') }}
                            </small>
                        </p>
                    </div>
                    <div>
                        <span class="icon-circle">
                            <svg class="icon">
                                <use href="#electric"/>
                            </svg>
                        </span>
                        <p>
                            {{ __('Trải nghiệm thuần điện') }}
                            <small>
                                {{ __('Êm ái, thông minh') }}
                                <br>
                                {{ __('và đầy cảm hứng') }}
                            </small>
                        </p>
                    </div>
                    <div>
                        <span class="icon-circle">
                            <svg class="icon">
                                <use href="#shield"/>
                            </svg>
                        </span>
                        <p>
                            {{ __('An tâm sở hữu') }}
                            <small>
                                {{ __('Tư vấn dịch vụ') }}
                                <br>
                                {{ __('và hỗ trợ sau bán hàng') }}
                            </small>
                        </p>
                    </div>
                </div>
            </section>
            <section class="fleet-intro container section-space" aria-labelledby="fleetTitle">
                <div class="fleet-copy">
                    <p class="eyebrow">
                        {{ __('DÒNG XE CỦA CHÚNG TÔI') }}
                    </p>
                    <h2 id="fleetTitle">
                        {{ __('Tìm chiếc xe phù hợp') }}
                        <br>
                        {{ __('với') }}
                        <span>
                            {{ __('hành trình của bạn.') }}
                        </span>
                    </h2>
                    <p class="lead">
                        {{ __('Từ chiếc SUV nhỏ gọn cho phố thị đến không gian rộng rãi cho gia đình. '
                            .'Khám phá dòng xe điện được thiết kế cho cách bạn sống.') }}
                    </p>
                    <a class="button button-dark" href="#models">
                        {{ __('Xem các dòng xe') }}
                        <svg class="icon">
                            <use href="#arrow"/>
                        </svg>
                    </a>
                    <div class="feature-grid">
                        <div>
                            <span class="feature-icon">
                                <svg class="icon">
                                    <use href="#calendar"/>
                                </svg>
                            </span>
                            <div>
                                <h3>
                                    {{ __('Đặt lịch dễ dàng') }}
                                </h3>
                                <p>
                                    {{ __('Chủ động chọn lịch') }}
                                    <br>
                                    {{ __('tư vấn và lái thử.') }}
                                </p>
                            </div>
                        </div>
                        <div>
                            <span class="feature-icon">
                                <svg class="icon">
                                    <use href="#shield"/>
                                </svg>
                            </span>
                            <div>
                                <h3>
                                    {{ __('Thông tin minh bạch') }}
                                </h3>
                                <p>
                                    {{ __('Tìm hiểu rõ về xe,') }}
                                    <br>
                                    {{ __('chi phí và chính sách.') }}
                                </p>
                            </div>
                        </div>
                        <div>
                            <span class="feature-icon">
                                <svg class="icon">
                                    <use href="#pin"/>
                                </svg>
                            </span>
                            <div>
                                <h3>
                                    {{ __('Tư vấn theo nhu cầu') }}
                                </h3>
                                <p>
                                    {{ __('Chọn chiếc xe phù hợp') }}
                                    <br>
                                    {{ __('với cuộc sống của bạn.') }}
                                </p>
                            </div>
                        </div>
                        <div>
                            <span class="feature-icon">
                                <svg class="icon">
                                    <use href="#support"/>
                                </svg>
                            </span>
                            <div>
                                <h3>
                                    {{ __('Đồng hành lâu dài') }}
                                </h3>
                                <p>
                                    {{ __('Hỗ trợ từ lúc chọn xe') }}
                                    <br>
                                    {{ __('đến trải nghiệm sở hữu.') }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <a
                    class="featured-car"
                    href="{{ route($clientRoutePrefix.'contact', ['type' => 'test_drive']) }}"
                    data-test-drive-open
                    data-model="VF 7"
                    aria-label="{{ __('Đăng ký trải nghiệm VinFast VF 7') }}"
                >
                    <div class="featured-car-image">
                        <span class="featured-kicker">
                            {{ __('THUẦN ĐIỆN. ĐẦY CÁ TÍNH.') }}
                        </span>
                        <img src="/assets/vinfast/vinfast-vf7-white.png" alt="VinFast VF 7" loading="lazy">
                        <div class="featured-caption">
                            <span>
                                <small>
                                    {{ __('KHÁM PHÁ') }}
                                </small>
                                VinFast VF 7
                            </span>
                            <span class="round-arrow">
                                <svg class="icon">
                                    <use href="#arrow"/>
                                </svg>
                            </span>
                        </div>
                    </div>
                </a>
            </section>
            <section id="experience" class="experience" aria-labelledby="experienceTitle">
                <div class="container experience-inner">
                    <div class="experience-copy">
                        <p class="eyebrow">
                            {{ __('VÌ SAO CHỌN VINFAST HÙNG VƯƠNG') }}
                        </p>
                        <h2 id="experienceTitle">
                            {{ __('Hơn cả một chiếc xe.') }}
                            <br>
                            {{ __('Một trải nghiệm') }}
                            <span>
                                {{ __('tốt hơn.') }}
                            </span>
                        </h2>
                        <p>
                            {{ __('Chúng tôi đặt sự thoải mái, an tâm và nhu cầu của bạn ở trung tâm. Từ '
                                .'lần lái thử đầu tiên đến mỗi hành trình tiếp theo.') }}
                        </p>
                        <a class="button button-primary" data-test-drive-open
                            href="{{ route($clientRoutePrefix.'contact', ['type' => 'test_drive']) }}">
                            {{ __('Trải nghiệm ngay') }}
                            <svg class="icon">
                                <use href="#arrow"/>
                            </svg>
                        </a>
                    </div>
                    <img src="/assets/vinfast/vinfast-vf6-red.png" alt="{{ __('VinFast VF 6 màu đỏ') }}" loading="lazy">
                </div>
            </section>
            <section id="models" class="models container section-space" aria-labelledby="modelsTitle">
                <div class="section-heading">
                    <div>
                        <p class="eyebrow">
                            {{ __('KHÁM PHÁ VINFAST') }}
                        </p>
                        <h2 id="modelsTitle">
                            {{ __('Những dòng xe') }}
                            <span>
                                {{ __('được yêu thích.') }}
                            </span>
                        </h2>
                        <p>
                            {{ __('Chọn cá tính của bạn. Chúng tôi giúp bạn tìm chiếc xe phù hợp.') }}
                        </p>
                    </div>
                    <div class="carousel-controls">
                        <button type="button" data-slide="-1" aria-label="{{ __('Xem dòng xe trước') }}">
                            <svg class="icon">
                                <use href="#arrow"/>
                            </svg>
                        </button>
                        <button type="button" data-slide="1" aria-label="{{ __('Xem dòng xe tiếp theo') }}">
                            <svg class="icon">
                                <use href="#arrow"/>
                            </svg>
                        </button>
                    </div>
                </div>
                <div class="model-cards"
                    id="modelCards" tabindex="0"
                    aria-label="{{ __('Danh sách dòng xe VinFast') }}">
                    @forelse($vehicles as $vehicle)
                        <article class="model-card">
                            <div class="model-image">
                                <img
                                    src="{{ $vehicle->media?->url() ?? asset('assets/vinfast/vinfast-vf7-white.png') }}"
                                    alt="{{ $vehicle->media?->alt ?: $vehicle->name }}"
                                    loading="lazy"
                                >
                            </div>
                            <div class="model-info">
                                <h3>
                                    {{ $vehicle->name }}
                                </h3>
                                <p>
                                    {{ $vehicle->segment }}
                                </p>
                                <a href="{{ route($clientRoutePrefix.'vehicles.show', $vehicle->slug) }}">
                                    {{ __('Khám phá mẫu xe ↗') }}
                                </a>
                            </div>
                        </article>
                    @empty
                        <p>
                            {{ __('Danh mục xe đang được cập nhật.') }}
                            <a href="{{ route($clientRoutePrefix.'contact') }}">
                                {{ __('Liên hệ đại lý') }}
                            </a>
                            .
                        </p>
                    @endforelse
                </div>
                <div class="road-cta">
                    <span class="icon-circle">
                        <svg class="icon">
                            <use href="#electric"/>
                        </svg>
                    </span>
                    <div>
                        <h3>
                            {{ __('Sẵn sàng cho hành trình mới?') }}
                        </h3>
                        <p>
                            {{ __('Lái thử hôm nay. Cảm nhận tương lai thuần điện.') }}
                        </p>
                    </div>
                    <a class="button button-primary" data-test-drive-open
                        href="{{ route($clientRoutePrefix.'contact', ['type' => 'test_drive']) }}">
                        {{ __('Bắt đầu ngay') }}
                        <svg class="icon">
                            <use href="#arrow"/>
                        </svg>
                    </a>
                </div>
            </section>
            <section id="journal" class="journal container" aria-labelledby="journalTitle">
                <div class="section-heading">
                    <div>
                        <p class="eyebrow">
                            {{ __('GÓC TƯ VẤN') }}
                        </p>
                        <h2 id="journalTitle">
                            {{ __('Hiểu xe.') }}
                            <span>
                                {{ __('Chọn đúng.') }}
                            </span>
                        </h2>
                        <p>
                            {{ __('Một chút chuẩn bị để mỗi hành trình thêm trọn vẹn.') }}
                        </p>
                    </div>
                </div>
                <div class="stories">
                    @forelse($posts as $post)
                        <article>
                            <div class="story-image">
                                @if($post->media)
                                    <img src="{{ $post->media->url() }}" alt="{{ $post->media->alt }}" loading="lazy">
                                @endif
                            </div>
                            <p class="eyebrow">
                                {{ __('GÓC TƯ VẤN') }}
                            </p>
                            <h3>
                                <a href="{{ route($clientRoutePrefix.'posts.show', $post->slug) }}">
                                    {{ $post->title }}
                                </a>
                            </h3>
                            <p>
                                {{ $post->excerpt }}
                            </p>
                        </article>
                    @empty
                        <p>
                            {{ __('Bài viết đang được cập nhật.') }}
                        </p>
                    @endforelse
                </div>
            </section>
            @if($promotions->isNotEmpty())
                <section class="container section-space">
                    <h2>
                        {{ __('Ưu đãi đang áp dụng') }}
                    </h2>
                    @foreach($promotions as $promotion)
                        <p>
                            <a href="{{ route($clientRoutePrefix.'promotions.show', $promotion->slug) }}">
                                {{ $promotion->title }}
                            </a>
                        </p>
                    @endforeach
                </section>
            @endif
            <section id="contact" class="contact">
                <div class="container contact-inner">
                    <div>
                        <p class="eyebrow">
                            {{ __('BẮT ĐẦU HÀNH TRÌNH') }}
                        </p>
                        <h2>
                            {{ __('Chiếc xe của bạn.') }}
                            <br>
                            <span>
                                {{ __('Trải nghiệm của bạn.') }}
                            </span>
                        </h2>
                        <p>
                            {{ __('Để lại nhu cầu để chuyên viên liên hệ tư vấn.') }}
                        </p>
                        <a class="button button-outline" data-test-drive-open
                            href="{{ route($clientRoutePrefix.'contact', ['type' => 'test_drive']) }}">
                            {{ __('Đặt lịch lái thử') }}
                        </a>
                    </div>
                    <form id="leadForm" method="post" action="{{ route($clientRoutePrefix.'leads.store') }}">
                        @csrf
                        <input type="hidden" name="type" value="consultation">
                        <x-feedback />
                        <div class="form-row">
                            <label>
                                {{ __('Họ và tên') }}
                                <input
                                    name="name"
                                    placeholder="{{ __('Nhập họ và tên…') }}"
                                    value="{{ old('name') }}"
                                    autocomplete="name"
                                    required
                                    maxlength="100"
                                >
                            </label>
                            <label>
                                {{ __('Số điện thoại') }}
                                <input
                                    name="phone"
                                    placeholder="{{ __('Ví dụ: 09xxxxxxxx') }}"
                                    value="{{ old('phone') }}"
                                    autocomplete="tel"
                                    type="tel"
                                    required
                                    maxlength="20"
                                >
                            </label>
                        </div>
                        <label>
                            {{ __('Mẫu xe quan tâm') }}
                            <select name="vehicle_id">
                                <option value="">
                                    {{ __('Chưa chọn') }}
                                </option>
                                @foreach($vehicles as $vehicle)
                                    <option value="{{ $vehicle->id }}">
                                        {{ $vehicle->name }}
                                    </option>
                                @endforeach
                            </select>
                        </label>
                        <label class="consent-row">
                            <input type="checkbox" name="consent" value="1" required>
                            {{ __('Tôi đồng ý cho đại lý liên hệ theo') }}
                            <a href="{{ route($clientRoutePrefix.'pages.show', 'bao-mat') }}">
                                {{ __('chính sách bảo mật') }}
                            </a>
                            .
                        </label>
                        <button class="button button-primary" type="submit">
                            {{ __('Gửi yêu cầu tư vấn ↗') }}
                        </button>
                    </form>
                </div>
            </section>
        </main>
        <footer>
            <div class="container footer-main">
                <a class="brand" href="#top">
                    <img class="brand-logo" src="/assets/vinfast/logo.png" alt="">
                    <span>
                        {{ __('HÙNG VƯƠNG') }}
                        <small>
                            {{ __('VINFAST · ĐẠI LÝ XE ĐIỆN') }}
                        </small>
                    </span>
                </a>
                <p>
                    {{ __('Chuyển động tiên phong.') }}
                    <br>
                    {{ __('Đồng hành trên mọi hành trình.') }}
                </p>
                <nav aria-label="{{ __('Điều hướng chân trang') }}">
                    <a href="#models">
                        {{ __('Dòng xe') }}
                    </a>
                    <a href="#experience">
                        {{ __('Trải nghiệm') }}
                    </a>
                    <a data-test-drive-open href="{{ route($clientRoutePrefix.'contact', ['type' => 'test_drive']) }}">
                        {{ __('Đăng ký lái thử') }}
                    </a>
                </nav>
            </div>
            <div class="container footer-bottom">
                <span>
                    ©
                    {{ date('Y') }}
                    {{ __('VinFast Hùng Vương') }}
                </span>
                <a href="{{ route($clientRoutePrefix.'pages.show', 'bao-mat') }}">
                    {{ __('Chính sách bảo mật') }}
                </a>
                <a href="{{ route($clientRoutePrefix.'contact') }}">
                    {{ __('Liên hệ') }}
                </a>
            </div>
        </footer>
        <x-site.test-drive-drawer :vehicles="$testDriveVehicles" />
        <script src="/js/home.js" defer>
        </script>
    </body>
</html>
