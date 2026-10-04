@props([
    'name', 'label', 'value' => null, 'type' => 'text', 'required' => false,
    'useOld' => true, 'hint' => null, 'rows' => 5,
])
@if(in_array($type, ['date', 'datetime-local']))
    <x-date-picker :name="$name" :label="$label" :value="$value" :type="$type"
        :required="$required" :use-old="$useOld" :hint="$hint" {{ $attributes }} />
@else
    @php
        $errorKey = trim(str_replace(['[', ']'], ['.', ''], $name), '.');
        $fieldId = $attributes->get('id', 'field-'.preg_replace('/[^a-zA-Z0-9_-]/', '-', $errorKey));
        $fieldValue = $useOld ? old($errorKey, $value) : $value;
        $description = trim(($hint ? $fieldId.'-hint ' : '').($errors->has($errorKey) ? $fieldId.'-error' : ''));
        $inputAttributes = $attributes->merge([
            'id' => $fieldId,
            'aria-invalid' => $errors->has($errorKey) ? 'true' : 'false',
            'aria-describedby' => $description ?: null,
        ]);
    @endphp
    <x-field-wrapper :name="$name" :label="$label" :id="$fieldId" :error-key="$errorKey"
        :hint="$hint" :required="$required">
        @if($type === 'textarea')
            <textarea name="{{ $name }}" rows="{{ $rows }}" {{ $inputAttributes }}
                @required($required)>{{ $fieldValue }}</textarea>
        @else
            <input
                type="{{ $type }}"
                name="{{ $name }}"
                @if(! in_array($type, ['password', 'file'])) value="{{ $fieldValue }}" @endif
                {{ $inputAttributes }}
                @required($required)
                @if($type === 'password' && ! $attributes->has('autocomplete')) autocomplete="new-password" @endif
            >
        @endif
    </x-field-wrapper>
@endif
