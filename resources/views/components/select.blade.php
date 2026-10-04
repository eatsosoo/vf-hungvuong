@props(['name', 'label', 'hint' => null, 'required' => false])
@php
    $errorKey = trim(str_replace(['[', ']'], ['.', ''], $name), '.');
    $fieldId = $attributes->get('id', 'field-'.preg_replace('/[^a-zA-Z0-9_-]/', '-', $errorKey));
    $description = trim(($hint ? $fieldId.'-hint ' : '').($errors->has($errorKey) ? $fieldId.'-error' : ''));
@endphp
<x-field-wrapper :name="$name" :label="$label" :id="$fieldId" :error-key="$errorKey"
    :hint="$hint" :required="$required">
    <select name="{{ $name }}" @required($required)
        {{ $attributes->merge([
            'id' => $fieldId,
            'aria-invalid' => $errors->has($errorKey) ? 'true' : 'false',
            'aria-describedby' => $description ?: null,
        ]) }}>
        {{ $slot }}
    </select>
</x-field-wrapper>
