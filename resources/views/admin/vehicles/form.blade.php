@extends('layouts.admin')
@section('title', 'Quản lý xe')
@section('content')
    @php
        $specifications = $vehicle->specifications
            ? json_encode($vehicle->specifications, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : null;
    @endphp
    <x-admin.page-heading :title="$vehicle->exists ? 'Chỉnh sửa xe' : 'Thêm mẫu xe'" />
    <form
        class="panel"
        method="post"
        action="{{ $vehicle->exists ? route('admin.vehicles.update', $vehicle) : route('admin.vehicles.store') }}"
    >
        @csrf
        @if($vehicle->exists)
            @method('PUT')
        @endif
        <div class="two-columns">
            <x-field name="name" label="Tên mẫu xe" :value="$vehicle->name" required />
            <x-field name="slug" label="Đường dẫn" :value="$vehicle->slug" required />
            <x-field name="segment" label="Phân khúc" :value="$vehicle->segment" />
            <x-field name="brochure_url" label="URL brochure" type="url" :value="$vehicle->brochure_url" />
        </div>
        <x-field name="description" label="Giới thiệu" type="textarea" :value="$vehicle->description" />
        <x-field
            name="specifications"
            label='Thông số dạng JSON (vd: {"Số chỗ": 5})'
            type="textarea"
            :value="$specifications" />
        <x-admin.media-picker :name="'media_id'" :selected="$vehicle->media_id"
            label="Ảnh chính" :media="$media" />
        <x-select name="is_active" label="Hiển thị">
            <option value="0">
                Ẩn
            </option>
            <option value="1" @selected(old('is_active', $vehicle->is_active))>
                Bật
            </option>
        </x-select>
        <h2>
            Phiên bản và giá tham khảo
        </h2>
        <p>
            Để trống tên để bỏ dòng. Giá tính bằng VNĐ; để trống nếu cần liên hệ.
        </p>
        <div data-repeater="variants">
            @foreach(old('variants', $vehicle->variants->toArray() ?: [['name' => '', 'price' => '']]) as $index =>
                $row)
                <div class="repeat-row two-columns">
                    <x-field :name="'variants['.$index.'][name]'" label="Tên phiên bản" :value="$row['name']" />
                    <x-field
                        :name="'variants['.$index.'][price]'"
                        label="Giá VNĐ"
                        type="number"
                        :value="$row['price'] ?? null" />
                </div>
            @endforeach
        </div>
        <button type="button" class="secondary" data-add-row="variants">
            Thêm phiên bản
        </button>
        <h2>
            Màu sắc
        </h2>
        <div data-repeater="colors">
            @foreach(old('colors',
                $vehicle->colors->toArray() ?: [['name' => '',
                'hex' => '',
                'media_id' => '']])
                as $index => $row)
                <div class="repeat-row two-columns">
                    <x-field :name="'colors['.$index.'][name]'" label="Tên màu" :value="$row['name']" />
                    <x-admin.color-picker
                        :name="'colors['.$index.'][hex]'"
                        label="Mã màu (#ffffff)"
                        :value="$row['hex'] ?? null" />
                    <x-admin.color-picker
                        :name="'colors['.$index.'][secondary_hex]'"
                        label="Màu nóc xe (nếu có)"
                        :value="$row['secondary_hex'] ?? null" />
                    <x-admin.media-picker :name="'colors['.$index.'][media_id]'" :selected="$row['media_id'] ?? null"
                        label="Ảnh màu xe" :media="$media" />
                </div>
            @endforeach
        </div>
        <button type="button" class="secondary" data-add-row="colors">
            Thêm màu
        </button>
        <div class="form-actions">
            <button>
                Lưu mẫu xe
            </button>
            <a href="{{ route('admin.vehicles.index') }}">
                Quay lại
            </a>
        </div>
    </form>
@endsection
