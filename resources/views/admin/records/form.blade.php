@extends('layouts.admin')
@section('title', $title)
@section('content')
    @php
        $formAction = $record->exists
            ? route('admin.'.$resource.'.update', $record)
            : route('admin.'.$resource.'.store');
        $labels = [
            'title' => 'Tiêu đề', 'slug' => 'Đường dẫn', 'body' => 'Nội dung Markdown',
            'seo_title' => 'Tiêu đề SEO', 'seo_description' => 'Mô tả SEO',
            'starts_at' => 'Bắt đầu (giờ Việt Nam)', 'ends_at' => 'Kết thúc (giờ Việt Nam)',
        ];
    @endphp
    <x-admin.page-heading :title="($record->exists ? 'Chỉnh sửa' : 'Thêm').' '.mb_strtolower($title)" />
    <form class="panel" method="post" action="{{ $formAction }}">
        @csrf
        @if($record->exists)
            @method('PUT')
        @endif
        @foreach($fields as $field)
            @if($field === 'is_active')
                <x-select name="is_active" label="Hiển thị">
                    <option value="0">Ẩn</option>
                    <option value="1" @selected(old('is_active', $record->is_active))>Bật</option>
                </x-select>
            @elseif($field === 'media_id')
                <x-admin.media-picker name="media_id" label="Ảnh" :selected="$record->media_id" :media="$media" />
            @else
                @php
                    $inputType = match ($field) {
                        'body', 'seo_description' => 'textarea',
                        'starts_at', 'ends_at' => 'datetime-local',
                        default => 'text',
                    };
                    $fieldValue = in_array($field, ['starts_at', 'ends_at'])
                        ? $record->{$field}?->format('Y-m-d\TH:i') : $record->{$field};
                @endphp
                <x-field :name="$field" :label="$labels[$field]" :type="$inputType" :value="$fieldValue"
                    :slug-source="$field === 'slug' ? 'title' : null" :slug-auto="! $record->exists"
                    :required="in_array($field, ['title', 'slug', 'body', 'starts_at', 'ends_at'])" />
            @endif
        @endforeach
        @if($resource === 'promotions')
            <x-select name="vehicles[]" label="Xe áp dụng" multiple size="5"
                hint="Giữ Ctrl (Windows) hoặc Command (Mac) để chọn nhiều mục.">
                @foreach($vehicles as $vehicle)
                    <option value="{{ $vehicle->id }}"
                        @selected(in_array($vehicle->id, old('vehicles', $selectedVehicles)))>
                        {{ $vehicle->name }}
                    </option>
                @endforeach
            </x-select>
        @endif
        <div class="form-actions">
            <button>Lưu nội dung</button>
            <a class="button secondary" href="{{ route('admin.'.$resource.'.index') }}">Quay lại</a>
        </div>
    </form>
@endsection
