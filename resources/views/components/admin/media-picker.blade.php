@props(['name', 'label', 'selected' => null, 'media'])
@php
    $errorKey = trim(str_replace(['[', ']'], ['.', ''], $name), '.');
    $fieldId = 'media-'.preg_replace('/[^a-zA-Z0-9_-]/', '-', $errorKey);
    $selectedId = old($errorKey, $selected);
    $selectedMedia = $media->firstWhere('id', $selectedId);
@endphp
<div class="media-picker" data-media-picker>
    <x-field-wrapper :name="$name" :label="$label" :id="$fieldId" :error-key="$errorKey">
        <input type="hidden" name="{{ $name }}" value="{{ $selectedId }}" data-media-value>
        <div class="media-picker-preview">
            <img @if($selectedMedia) src="{{ $selectedMedia->url() }}" @endif alt="{{ $selectedMedia?->alt ?? '' }}"
                width="144" height="96" data-media-preview @if(! $selectedMedia) hidden @endif>
            <div class="min-w-0">
                <strong data-media-name>
                    {{ $selectedMedia?->original_name ?? ($selectedId ? 'Ảnh #'.$selectedId : 'Chưa chọn ảnh') }}
                </strong>
                <small data-media-description>{{ $selectedMedia?->alt ?? 'Tải ảnh mới hoặc chọn từ thư viện.' }}</small>
            </div>
        </div>
        <div class="media-picker-actions">
            <button type="button" class="secondary" id="{{ $fieldId }}" data-media-open
                aria-haspopup="dialog" aria-controls="admin-media-dialog"
                aria-invalid="{{ $errors->has($errorKey) ? 'true' : 'false' }}"
                @if($errors->has($errorKey)) aria-describedby="{{ $fieldId }}-error" @endif>
                <x-admin.icon name="folder" />Chọn từ thư viện
            </button>
            <button type="button" class="secondary" data-media-upload-open>
                <x-admin.icon name="plus" />Tải ảnh mới
            </button>
            <button type="button" class="secondary" data-media-clear @if(! $selectedId) hidden @endif>Bỏ chọn</button>
        </div>
        <input type="file" accept="image/jpeg,image/png,image/webp" data-media-upload hidden
            aria-label="Tải {{ mb_strtolower($label) }}">
        <small class="field-hint" data-media-status role="status" aria-live="polite"></small>
        <noscript>
            <label for="{{ $fieldId }}-manual">ID ảnh từ <a href="{{ route('admin.media.index') }}">thư viện</a></label>
            <input type="number" name="{{ $name }}" id="{{ $fieldId }}-manual" value="{{ $selectedId }}" min="1"
                placeholder="Nhập ID ảnh, ví dụ: 123">
        </noscript>
    </x-field-wrapper>
</div>
