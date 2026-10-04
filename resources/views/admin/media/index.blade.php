@extends('layouts.admin')
@section('title', 'Thư viện ảnh')
@section('content')
    <x-admin.page-heading title="Thư viện ảnh" />
    <form class="panel media-upload-panel" method="post" enctype="multipart/form-data"
        action="{{ route('admin.media.store') }}" data-batch-upload
        data-max-file-bytes="{{ $maxUploadBytes }}" data-max-batch-bytes="{{ $maxBatchBytes }}">
        @csrf
        <div class="media-section-heading">
            <div>
                <h2>Thêm ảnh vào thư viện</h2>
                <p>Chọn nhiều ảnh, kiểm tra bản xem trước và mô tả alt trước khi tải lên.</p>
            </div>
            <span class="badge">Tối đa 20 ảnh / lần</span>
        </div>
        <label class="media-dropzone" for="media-files" data-upload-dropzone>
            <input class="sr-only" type="file" id="media-files" name="images[]" multiple required
                accept="image/jpeg,image/png,image/webp" data-upload-files aria-describedby="media-upload-help">
            <x-admin.icon name="image" class="size-8" />
            <strong>Chọn ảnh hoặc kéo thả vào đây</strong>
            <span id="media-upload-help">
                JPG, PNG, WebP · tối đa {{ round($maxUploadBytes / 1024 / 1024, 1) }} MB mỗi ảnh
                · alt mặc định là tên file
            </span>
        </label>
        <div class="upload-queue-heading" data-upload-queue-heading hidden>
            <strong data-upload-count></strong>
            <button type="button" class="secondary" data-upload-clear>Bỏ tất cả</button>
        </div>
        <div class="upload-queue" data-upload-queue></div>
        <p class="field-hint" role="status" aria-live="polite" data-upload-status></p>
        <div class="media-upload-footer">
            <small>
                Ảnh được tối ưu WebP sau khi tải lên.
                Tổng ảnh mỗi lần tối đa {{ round($maxBatchBytes / 1024 / 1024, 1) }} MB.
            </small>
            <button type="submit" data-upload-submit><x-admin.icon name="plus" />Tải ảnh đã chọn</button>
        </div>
    </form>
    <section aria-labelledby="media-library-heading">
        <div class="media-section-heading">
            <div>
                <h2 id="media-library-heading">Ảnh trong thư viện</h2>
                <p>{{ number_format($media->total(), 0, ',', '.') }} ảnh · Tìm theo tên file hoặc mô tả alt</p>
            </div>
        </div>
        <form class="media-toolbar" method="get">
            <x-field name="q" label="Tìm ảnh" :value="request('q')" placeholder="Tên file hoặc mô tả alt" />
            <x-select name="sort" label="Sắp xếp">
                @foreach(['newest' => 'Ngày thêm: mới nhất', 'oldest' => 'Ngày thêm: cũ nhất',
                    'name_asc' => 'Tên ảnh: A–Z', 'name_desc' => 'Tên ảnh: Z–A'] as $value => $label)
                    <option value="{{ $value }}" @selected(request('sort', 'newest') === $value)>{{ $label }}</option>
                @endforeach
            </x-select>
            <x-select name="columns" label="Ảnh mỗi hàng" data-media-columns data-grid-target="library"
                data-explicit-columns="{{ request()->has('columns') ? 'true' : 'false' }}">
                @foreach([2, 3, 4, 6] as $columns)
                    <option value="{{ $columns }}" @selected((int) request('columns', 4) === $columns)>
                        {{ $columns }} ảnh / hàng
                    </option>
                @endforeach
            </x-select>
            <button>Tìm ảnh</button>
            @if(request('q'))
                <a class="button secondary" href="{{ route('admin.media.index') }}">Xóa bộ lọc</a>
            @endif
        </form>
        <div class="media-grid" data-media-grid="library" data-columns="{{ request('columns', 4) }}">
            @forelse($media as $item)
                <article class="media-card">
                    <img class="media-card-image" src="{{ $item->url() }}" alt="{{ $item->alt }}"
                        loading="lazy" width="{{ $item->width }}" height="{{ $item->height }}">
                    <div class="media-card-body">
                        <strong class="media-card-name" title="{{ $item->original_name }}">
                            {{ $item->original_name }}
                        </strong>
                        <small>#{{ $item->id }} · {{ $item->width }} × {{ $item->height }}
                            · {{ round($item->size / 1024) }} KB</small>
                        <small>Thêm {{ $item->created_at->format('d/m/Y H:i') }}</small>
                        <p class="media-card-alt">{{ $item->alt ?: 'Chưa có mô tả alt' }}</p>
                        <details class="media-card-details">
                            <summary>Chỉnh sửa alt / lấy URL</summary>
                            <x-field :name="'media_url_'.$item->id" label="URL ảnh" :value="$item->url()"
                                :use-old="false" readonly />
                            <form method="post" action="{{ route('admin.media.update', $item) }}">
                                @csrf
                                @method('PUT')
                                <x-field name="alt" :id="'media-alt-'.$item->id" label="Mô tả alt"
                                    :value="$item->alt" :use-old="false" maxlength="255" />
                                <button>Cập nhật alt</button>
                            </form>
                        </details>
                    </div>
                </article>
            @empty
                <div class="media-empty">
                    <x-admin.empty-state :title="request('q') ? 'Không tìm thấy ảnh' : 'Thư viện chưa có ảnh'"
                        description="Thử từ khóa khác hoặc tải ảnh bằng khu vực phía trên." />
                </div>
            @endforelse
        </div>
        <x-admin.pagination :records="$media" :show-page-size="false" />
    </section>
@endsection
