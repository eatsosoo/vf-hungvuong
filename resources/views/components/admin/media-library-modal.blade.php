@php
    $postLimit = (int) ini_parse_quantity(ini_get('post_max_size'));
    $maxBatchBytes = $postLimit > 0 ? max(1, $postLimit - 65536) : 100 * 1024 * 1024;
@endphp
<dialog class="media-dialog" id="admin-media-dialog" data-media-dialog
    data-library-url="{{ route('admin.media.index') }}" data-upload-url="{{ route('admin.media.store') }}"
    data-max-file-bytes="{{ min(5 * 1024 * 1024, (int) \Illuminate\Http\UploadedFile::getMaxFilesize()) }}"
    data-max-batch-bytes="{{ $maxBatchBytes }}"
    aria-labelledby="media-dialog-title" aria-describedby="media-dialog-description">
    <header class="media-dialog-header">
        <div>
            <h2 id="media-dialog-title">Chọn ảnh từ thư viện</h2>
            <p id="media-dialog-description">Chọn một ảnh để sử dụng cho mẫu xe hoặc nội dung đang chỉnh sửa.</p>
        </div>
        <button type="button" class="secondary" data-media-dialog-close aria-label="Đóng thư viện ảnh">
            <x-admin.icon name="close" />
        </button>
    </header>
    <form class="media-toolbar" data-media-search>
        <x-field name="library_q" label="Tìm ảnh" placeholder="Tên file hoặc mô tả alt" maxlength="100" />
        <x-select name="library_sort" label="Sắp xếp">
            <option value="newest">Ngày thêm: mới nhất</option>
            <option value="oldest">Ngày thêm: cũ nhất</option>
            <option value="name_asc">Tên ảnh: A–Z</option>
            <option value="name_desc">Tên ảnh: Z–A</option>
        </x-select>
        <x-select name="library_columns" label="Ảnh mỗi hàng" data-media-columns data-grid-target="modal">
            @foreach([2, 3, 4, 6] as $columns)
                <option value="{{ $columns }}" @selected($columns === 4)>{{ $columns }} ảnh / hàng</option>
            @endforeach
        </x-select>
        <button type="submit">Tìm ảnh</button>
    </form>
    <div class="media-dialog-content">
        <p class="media-library-status" role="status" aria-live="polite" data-library-status></p>
        <div class="media-grid" data-media-grid="modal" data-columns="4" data-library-results></div>
    </div>
    <footer class="media-dialog-footer">
        <button type="button" class="secondary" data-library-prev>Trang trước</button>
        <span data-library-page class="text-sm text-muted"></span>
        <button type="button" class="secondary" data-library-next>Trang sau</button>
    </footer>
</dialog>
