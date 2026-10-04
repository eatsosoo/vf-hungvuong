@extends('layouts.admin')
@section('title', 'Quản lý xe')
@section('content')
    @php
        $specifications = $vehicle->specifications
            ? json_encode($vehicle->specifications, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : null;
        $colorRows = old('colors', $vehicle->colors->toArray() ?: [['name' => '', 'hex' => '', 'media_id' => '']]);
        $colorRows = is_array($colorRows) ? $colorRows : [];
        $suggestedColors = [
            ['Trắng', '#ffffff'], ['Đen', '#171717'], ['Xám', '#808080'], ['Bạc', '#c0c0c0'],
            ['Đỏ', '#b91c1c'], ['Xanh dương', '#2563eb'], ['Xanh lá', '#166534'], ['Cam', '#ea580c'],
        ];
    @endphp
    <x-admin.page-heading :title="$vehicle->exists ? 'Chỉnh sửa xe' : 'Thêm mẫu xe'"
        description="Quản lý thông tin, phiên bản và nhiều màu xe trong một lần lưu.">
        <a class="button secondary" href="{{ route('admin.vehicles.index') }}">Danh mục xe</a>
    </x-admin.page-heading>
    <form class="vehicle-editor-layout" method="post" data-vehicle-editor
        action="{{ $vehicle->exists ? route('admin.vehicles.update', $vehicle) : route('admin.vehicles.store') }}">
        @csrf
        @if($vehicle->exists)
            @method('PUT')
        @endif
        <section class="form-section">
            <h2 class="section-heading">Thông tin mẫu xe</h2>
            <p class="section-description">Tên, phân khúc và nội dung giới thiệu hiển thị trên website.</p>
            <div class="two-columns">
                <x-field name="name" label="Tên mẫu xe" :value="$vehicle->name" required />
                <x-field name="slug" label="Đường dẫn" :value="$vehicle->slug"
                    slug-source="name" :slug-auto="! $vehicle->exists" required />
                <x-field name="segment" label="Phân khúc" :value="$vehicle->segment" />
                <x-field name="brochure_url" label="URL brochure" type="url" :value="$vehicle->brochure_url" />
            </div>
            <x-field name="description" label="Giới thiệu" type="textarea" :value="$vehicle->description" />
            <x-field name="specifications" label="Thông số kỹ thuật (JSON)" type="textarea"
                hint='Ví dụ: {"Số chỗ": 5}. Giữ tên thông số và đơn vị rõ ràng.' :value="$specifications" />
        </section>
        <section class="form-section">
            <h2 class="section-heading">Ảnh và hiển thị</h2>
            <p class="section-description">Ảnh đại diện dùng cho danh mục. Mỗi màu có ảnh riêng ở bên dưới.</p>
            <x-admin.media-picker :name="'media_id'" :selected="$vehicle->media_id" label="Ảnh chính" :media="$media" />
            <x-select name="is_active" label="Trạng thái hiển thị">
                <option value="0">Ẩn trên website</option>
                <option value="1" @selected(old('is_active', $vehicle->is_active))>Hiển thị trên website</option>
            </x-select>
        </section>
        <section class="form-section vehicle-editor-wide">
            <h2 class="section-heading">Phiên bản và giá tham khảo</h2>
            <p class="section-description">Để trống tên để bỏ dòng. Giá tính bằng VNĐ; để trống nếu cần liên hệ.</p>
            <div data-repeater="variants">
                @foreach(old('variants', $vehicle->variants->toArray() ?: [['name' => '', 'price' => '']])
                    as $index => $row)
                    <div class="repeat-row two-columns">
                        <x-field :name="'variants['.$index.'][name]'" label="Tên phiên bản" :value="$row['name']" />
                        <x-field :name="'variants['.$index.'][price]'" label="Giá VNĐ" type="number"
                            :value="$row['price'] ?? null" min="0" />
                    </div>
                @endforeach
            </div>
            <button type="button" class="secondary" data-add-row="variants">
                <x-admin.icon name="plus" />Thêm phiên bản
            </button>
        </section>
        <section class="form-section vehicle-editor-wide" aria-labelledby="vehicle-colors-heading">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h2 class="section-heading" id="vehicle-colors-heading">Màu ngoại thất</h2>
                    <p class="section-description">Thêm tối đa 30 màu, mỗi màu có mã HEX và ảnh xe riêng.</p>
                </div>
                <span class="badge" data-vehicle-color-count>{{ count($colorRows) }}/30 màu</span>
            </div>
            <fieldset class="vehicle-color-presets">
                <legend>Thêm nhanh nhiều màu</legend>
                <p class="text-sm text-muted">Chọn các màu gợi ý rồi chỉnh tên, mã màu và ảnh theo xe thực tế.</p>
                <div class="vehicle-color-preset-grid">
                    @foreach($suggestedColors as [$name, $hex])
                        <label class="vehicle-color-preset">
                            <input type="checkbox" data-color-preset data-name="{{ $name }}" data-hex="{{ $hex }}">
                            <svg viewBox="0 0 32 32" aria-hidden="true">
                                <circle cx="16" cy="16" r="15" fill="{{ $hex }}" stroke="#dee4df" />
                            </svg>
                            <span>{{ $name }}</span>
                        </label>
                    @endforeach
                </div>
                <div class="actions mt-4">
                    <button type="button" data-add-preset-colors>
                        <x-admin.icon name="plus" />Thêm màu đã chọn
                    </button>
                    <button type="button" class="secondary" data-add-vehicle-color>
                        <x-admin.icon name="plus" />Thêm màu khác
                    </button>
                </div>
            </fieldset>
            <p class="vehicle-color-editor-status" data-vehicle-color-status role="status" aria-live="polite"></p>
            <input type="hidden" name="colors" value="">
            <div class="vehicle-color-editor-grid" data-vehicle-color-rows>
                @foreach($colorRows as $index => $row)
                    <x-admin.vehicle-color-row :index="$index" :row="$row" :media="$media" />
                @endforeach
            </div>
            <template data-vehicle-color-template>
                <x-admin.vehicle-color-row index="__INDEX__" :media="collect()" />
            </template>
            <noscript><p>Để thêm nhiều màu, bật JavaScript. Các màu hiện có vẫn có thể chỉnh sửa và lưu.</p></noscript>
        </section>
        <div class="form-footer vehicle-editor-wide">
            <p>Thông tin, phiên bản và tất cả màu xe được lưu cùng một lần.</p>
            <div class="form-actions">
                <a class="button secondary" href="{{ route('admin.vehicles.index') }}">Quay lại</a>
                <button type="submit"><x-admin.icon name="save" />Lưu mẫu xe</button>
            </div>
        </div>
    </form>
@endsection
