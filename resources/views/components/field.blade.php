@props([
    'name', 'label', 'value' => null, 'type' => 'text', 'required' => false,
    'useOld' => true, 'hint' => null, 'rows' => 5,
    'slugSource' => null, 'slugAuto' => true,
])
@if(in_array($type, ['date', 'datetime-local']))
    <x-date-picker :name="$name" :label="$label" :value="$value" :type="$type"
        :required="$required" :use-old="$useOld" :hint="$hint" {{ $attributes }} />
@else
    @php
        $errorKey = trim(str_replace(['[', ']'], ['.', ''], $name), '.');
        $fieldId = $attributes->get('id', 'field-'.preg_replace('/[^a-zA-Z0-9_-]/', '-', $errorKey));
        $fieldValue = $useOld ? old($errorKey, $value) : $value;
        if ($slugSource) {
            $hint = trim(($hint ? $hint.' ' : '').
                'Tự tạo theo tiêu đề/tên. Có thể chỉnh thủ công; xóa đường dẫn để tạo lại.');
        }
        $description = trim(($hint ? $fieldId.'-hint ' : '').($errors->has($errorKey) ? $fieldId.'-error' : ''));
        $inputKey = Str::afterLast($errorKey, '.');
        $labelExamples = [
            'Tên mẫu xe' => 'Ví dụ: VinFast VF 7',
            'Tên phiên bản' => 'Ví dụ: VF 7 Plus',
            'Tên màu' => 'Ví dụ: Trắng',
            'Phân khúc' => 'Ví dụ: SUV điện',
            'Giờ mở cửa' => 'Ví dụ: 08:00–18:00, thứ Hai–Chủ Nhật',
        ];
        $placeholder = match (true) {
            in_array($type, ['file', 'hidden', 'checkbox', 'radio', 'color', 'range']) => null,
            $type === 'email', $inputKey === 'email' => __('Ví dụ: email@example.com'),
            $type === 'tel' => __('Ví dụ: 09xxxxxxxx'),
            $type === 'password' => __('Nhập mật khẩu…'),
            $type === 'url' => 'https://example.com',
            $type === 'number' => __('Nhập số…'),
            $type === 'month' => 'MM/YYYY',
            $type === 'time' => 'HH:mm',
            $inputKey === 'slug' => __('Ví dụ: ten-noi-dung'),
            $inputKey === 'q', $type === 'search' => __('Nhập từ khóa tìm kiếm…'),
            $inputKey === 'specifications' => '{"Số chỗ": 5}',
            isset($labelExamples[$label]) => __($labelExamples[$label]),
            default => __('Nhập :label…', ['label' => mb_strtolower(__($label))]),
        };
        $inputAttributes = $attributes->merge([
            'id' => $fieldId,
            'data-slug-source' => $slugSource,
            'data-slug-auto' => $slugSource ? ($slugAuto ? 'true' : 'false') : null,
            'maxlength' => $slugSource ? 180 : null,
            'placeholder' => $placeholder,
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
