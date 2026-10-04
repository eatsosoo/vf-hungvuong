@props([
    'name', 'label', 'value' => null, 'type' => 'date', 'required' => false,
    'useOld' => true, 'hint' => null,
])
@php
    $errorKey = trim(str_replace(['[', ']'], ['.', ''], $name), '.');
    $fieldId = $attributes->get('id', 'field-'.preg_replace('/[^a-zA-Z0-9_-]/', '-', $errorKey));
    $fieldValue = $useOld ? old($errorKey, $value) : $value;
    $description = trim(($hint ? $fieldId.'-hint ' : '').($errors->has($errorKey) ? $fieldId.'-error' : ''));
    $hasTime = $type === 'datetime-local';
    $weekdays = [
        'Thứ hai' => 'T2', 'Thứ ba' => 'T3', 'Thứ tư' => 'T4', 'Thứ năm' => 'T5',
        'Thứ sáu' => 'T6', 'Thứ bảy' => 'T7', 'Chủ nhật' => 'CN',
    ];
@endphp
<x-field-wrapper :name="$name" :label="$label" :id="$fieldId" :error-key="$errorKey"
    :hint="$hint" :required="$required">
    <div class="date-picker" data-date-picker data-timezone="{{ config('app.timezone') }}">
        <div class="date-picker-control">
            <input
                type="{{ $hasTime ? 'datetime-local' : 'date' }}"
                name="{{ $name }}"
                value="{{ $fieldValue }}"
                data-date-picker-input
                {{ $attributes->merge([
                    'id' => $fieldId,
                    'aria-invalid' => $errors->has($errorKey) ? 'true' : 'false',
                    'aria-describedby' => $description ?: null,
                ]) }}
                @required($required)
            >
            <button
                type="button"
                class="date-picker-toggle"
                data-date-picker-toggle
                aria-label="{{ __('Chọn :label', ['label' => __($label)]) }}"
                aria-haspopup="dialog"
                aria-expanded="false"
                aria-controls="{{ $fieldId }}-calendar"
                hidden
            >
                <x-admin.icon name="calendar" />
            </button>
        </div>
        <dialog
            class="date-picker-panel"
            id="{{ $fieldId }}-calendar"
            aria-labelledby="{{ $fieldId }}-calendar-title"
            aria-describedby="{{ $fieldId }}-calendar-help"
            data-date-picker-panel
        >
            <div class="date-picker-heading">
                <strong id="{{ $fieldId }}-calendar-title">{{ $hasTime ? __('Chọn ngày và giờ') : __('Chọn ngày') }}</strong>
                <button type="button" class="date-picker-icon" data-date-picker-close aria-label="{{ __('Đóng lịch') }}">
                    <x-admin.icon name="close" />
                </button>
            </div>
            <div class="date-picker-navigation">
                <button type="button" class="date-picker-icon" data-date-picker-previous aria-label="{{ __('Tháng trước') }}">
                    <x-admin.icon name="arrow" class="rotate-180" />
                </button>
                <div class="date-picker-month-year">
                    <label class="sr-only" for="{{ $fieldId }}-month">{{ __('Tháng') }}</label>
                    <select id="{{ $fieldId }}-month" form="{{ $fieldId }}-picker-controls" data-date-picker-month>
                        @for($month = 1; $month <= 12; $month++)
                            <option value="{{ $month }}">{{ __('Tháng :month', ['month' => $month]) }}</option>
                        @endfor
                    </select>
                    <label class="sr-only" for="{{ $fieldId }}-year">{{ __('Năm') }}</label>
                    <input id="{{ $fieldId }}-year" type="number" min="1" max="9999"
                        form="{{ $fieldId }}-picker-controls" data-date-picker-year>
                </div>
                <button type="button" class="date-picker-icon" data-date-picker-next aria-label="{{ __('Tháng sau') }}">
                    <x-admin.icon name="arrow" />
                </button>
            </div>
            <span class="sr-only" id="{{ $fieldId }}-month-title" aria-live="polite" data-date-picker-title></span>
            <table class="date-picker-grid" role="grid" aria-labelledby="{{ $fieldId }}-month-title">
                <thead>
                    <tr>
                        @foreach($weekdays as $weekday => $shortName)
                            <th scope="col" abbr="{{ __($weekday) }}">{{ __($shortName) }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody data-date-picker-days></tbody>
            </table>
            @if($hasTime)
                <div class="date-picker-time">
                    <label for="{{ $fieldId }}-time">{{ __('Giờ') }}</label>
                    <input id="{{ $fieldId }}-time" type="time"
                        form="{{ $fieldId }}-picker-controls"
                        aria-describedby="{{ $fieldId }}-calendar-message" data-date-picker-time>
                </div>
                <p class="date-picker-selection" aria-live="polite" data-date-picker-selection></p>
            @endif
            <p class="date-picker-message" id="{{ $fieldId }}-calendar-message"
                role="status" data-date-picker-message hidden></p>
            <div class="date-picker-footer">
                <button type="button" class="date-picker-action" data-date-picker-today>{{ __('Hôm nay') }}</button>
                <button type="button" class="date-picker-action" data-date-picker-clear>{{ __('Xóa') }}</button>
                @if($hasTime)
                    <button type="button" class="date-picker-apply" data-date-picker-apply>{{ __('Áp dụng') }}</button>
                @endif
            </div>
            <p class="date-picker-help" id="{{ $fieldId }}-calendar-help">
                {{ __('Phím mũi tên để di chuyển · Enter để chọn · Esc để đóng') }}
            </p>
        </dialog>
    </div>
</x-field-wrapper>
