@props(['name', 'label', 'value' => null])
@php
    $errorKey = trim(str_replace(['[', ']'], ['.', ''], $name), '.');
    $fieldId = 'color-'.preg_replace('/[^a-zA-Z0-9_-]/', '-', $errorKey);
    $fieldValue = old($errorKey, $value);
    $swatch = preg_match('/\A#[0-9a-fA-F]{6}\z/', $fieldValue ?? '') ? $fieldValue : '#ffffff';
@endphp
<x-field-wrapper :name="$name" :label="$label" :id="$fieldId" :error-key="$errorKey">
    <div class="color-control" data-color-control>
        <input type="color" value="{{ $swatch }}" data-color-swatch aria-label="Chọn {{ mb_strtolower($label) }}">
        <input type="text" name="{{ $name }}" id="{{ $fieldId }}" value="{{ $fieldValue }}"
            placeholder="#ffffff" maxlength="7" pattern="#[0-9a-fA-F]{6}" data-color-text
            aria-invalid="{{ $errors->has($errorKey) ? 'true' : 'false' }}"
            @if($errors->has($errorKey)) aria-describedby="{{ $fieldId }}-error" @endif>
        <button type="button" class="secondary color-clear" data-color-clear
            aria-label="Xóa {{ mb_strtolower($label) }}"><x-admin.icon name="close" /></button>
    </div>
</x-field-wrapper>
