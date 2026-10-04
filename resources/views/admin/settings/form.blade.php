@extends('layouts.admin')
@section('title', 'Cấu hình website')
@section('content')
    <x-admin.page-heading title="Cấu hình website"
        description="Cập nhật thông tin đại lý, nội dung trang chủ và cách nhận thông báo." />
    <form method="post" action="{{ route('admin.settings.update') }}">
        @csrf
        @method('PUT')
        <div class="editor-grid">
            <section class="panel">
                <h2>Thông tin đại lý</h2>
                <div class="two-columns">
                    <x-field name="site_name" label="Tên website" :value="$settings['site_name'] ?? null" required />
                    <x-field name="hotline" label="Hotline" type="tel" :value="$settings['hotline'] ?? null" />
                    <x-field name="address" label="Địa chỉ" :value="$settings['address'] ?? null" />
                    <x-field name="opening_hours" label="Giờ mở cửa" :value="$settings['opening_hours'] ?? null" />
                    <x-field name="zalo_url" label="URL Zalo" type="url" :value="$settings['zalo_url'] ?? null" />
                    <x-field name="map_url" label="URL bản đồ" type="url" :value="$settings['map_url'] ?? null" />
                </div>
                <x-field name="seo_description" label="Mô tả SEO mặc định" type="textarea"
                    :value="$settings['seo_description'] ?? null" :rows="3" />
            </section>
            <section class="panel">
                <h2>Thông báo</h2>
                <x-select name="notify_leads" label="Thông báo khách mới trong admin">
                    <option value="1">Bật</option>
                    <option value="0" @selected(old('notify_leads', $settings['notify_leads'] ?? '1') === '0')>
                        Tắt
                    </option>
                </x-select>
                <p class="text-sm text-muted">Thông báo giúp đội ngũ tiếp nhận các yêu cầu tư vấn và lái thử.</p>
            </section>
        </div>
        <section class="panel">
            <h2>Nội dung trang chủ</h2>
            <x-field name="hero_title" label="Tiêu đề banner" :value="$settings['hero_title'] ?? null" />
            <x-field name="hero_description" label="Nội dung banner" type="textarea" :rows="3"
                :value="$settings['hero_description'] ?? null" />
            <x-admin.media-picker name="banner_media_id" label="Ảnh banner" :media="$media"
                :selected="$settings['banner_media_id'] ?? null" />
            <div class="form-actions"><button>Lưu cấu hình</button></div>
        </section>
    </form>
@endsection
