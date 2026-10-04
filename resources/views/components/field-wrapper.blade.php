@props(['name', 'label', 'id', 'errorKey', 'hint' => null, 'required' => false])
<div class="field">
    <label class="field-label" for="{{ $id }}">
        {{ __($label) }}
        @if($required)
            <span aria-hidden="true" class="text-red-800">*</span>
            <span class="sr-only">{{ __('(bắt buộc)') }}</span>
        @endif
    </label>
    {{ $slot }}
    @if($hint)
        <small class="field-hint" id="{{ $id }}-hint">{{ __($hint) }}</small>
    @endif
    @error($errorKey)
        <small class="error" id="{{ $id }}-error">{{ $message }}</small>
    @enderror
</div>
