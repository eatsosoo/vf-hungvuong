@extends('layouts.admin')
@section('title', 'Trang nội dung')
@push('assets')
    @vite(['resources/css/editor.css', 'resources/js/editor.js'])
@endpush
@section('content')
    <x-admin.page-heading :title="$record->exists ? 'Chỉnh sửa trang nội dung' : 'Tạo trang nội dung'"
        description="Biên tập các trang giới thiệu, chính sách và thông tin của đại lý.">
        <a class="button secondary" href="{{ route('admin.pages.index') }}">
            <x-admin.icon name="arrow" class="rotate-180" />Danh sách trang
        </a>
    </x-admin.page-heading>
    <form class="content-layout" method="post"
        action="{{ $record->exists ? route('admin.pages.update', $record) : route('admin.pages.store') }}">
        @csrf
        @if($record->exists)
            @method('PUT')
        @endif
        <div class="content-main">
            <section class="panel form-section" aria-labelledby="page-information-title">
                <h2 class="section-heading" id="page-information-title">Thông tin trang</h2>
                <p class="section-description">Đặt tiêu đề và đường dẫn giúp khách hàng dễ tìm thấy nội dung.</p>
                <x-field name="title" label="Tiêu đề trang" :value="$record->title" maxlength="255" required />
                <x-field name="slug" label="Đường dẫn" :value="$record->slug" maxlength="180"
                    slug-source="title" :slug-auto="! $record->exists" required
                    hint="Viết thường, không dấu và nối từ bằng gạch ngang. Ví dụ: gioi-thieu-dai-ly." />
            </section>
            <section class="panel form-section" aria-labelledby="page-content-title">
                <h2 class="section-heading" id="page-content-title">Biên tập nội dung</h2>
                <p class="section-description">Sử dụng tiêu đề, danh sách, hình ảnh và bảng để nội dung dễ đọc.</p>
                <x-admin.post-editor :record="$record" :media="$media" scope="page"
                    label="Nội dung trang" placeholder="Bắt đầu viết nội dung trang…" />
            </section>
        </div>
        <aside class="content-side">
            <section class="panel form-section" aria-labelledby="page-publishing-title">
                <h2 class="section-heading" id="page-publishing-title">Hiển thị trên website</h2>
                <p class="section-description">Trang đang ẩn sẽ không xuất hiện trên website công khai.</p>
                <x-select name="is_active" label="Trạng thái hiển thị">
                    <option value="0" @selected(! old('is_active', $record->is_active))>Đang ẩn</option>
                    <option value="1" @selected(old('is_active', $record->is_active))>Đang hiển thị</option>
                </x-select>
                @if($record->exists)
                    <dl class="grid gap-3 border-t border-line pt-4 text-sm">
                        <div class="flex justify-between gap-3">
                            <dt class="text-muted">Ngày tạo</dt>
                            <dd>{{ $record->created_at->format('d/m/Y H:i') }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-muted">Cập nhật</dt>
                            <dd>{{ $record->updated_at->format('d/m/Y H:i') }}</dd>
                        </div>
                    </dl>
                @endif
            </section>
            <section class="panel form-section" aria-labelledby="page-seo-title">
                <h2 class="section-heading" id="page-seo-title">Thông tin tìm kiếm</h2>
                <p class="section-description">Giới thiệu ngắn gọn nội dung trang trên kết quả tìm kiếm.</p>
                <x-field name="seo_title" label="Tiêu đề SEO" :value="$record->seo_title" maxlength="255"
                    hint="Để trống để sử dụng tiêu đề trang." />
                <x-field name="seo_description" label="Mô tả SEO" type="textarea"
                    :value="$record->seo_description" :rows="4" maxlength="500" />
            </section>
        </aside>
        <div class="panel form-footer">
            <p class="field-hint">Các trường có dấu <span aria-hidden="true">*</span> là bắt buộc.</p>
            <div class="form-actions">
                <a class="button secondary" href="{{ route('admin.pages.index') }}">Hủy</a>
                <button type="submit"><x-admin.icon name="check" />Lưu nội dung</button>
            </div>
        </div>
    </form>
    @stack('editor-dialogs')
@endsection
