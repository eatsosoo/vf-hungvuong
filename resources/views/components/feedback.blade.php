@props(['interactive' => false])
@if(session('success'))
    <div class="notice" role="status">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="error-box" role="alert" aria-labelledby="error-summary-title"
        @if($interactive) tabindex="-1" data-error-summary @endif>
        <strong id="error-summary-title">{{ __('Vui lòng kiểm tra lại thông tin:') }}</strong>
        <ul>
            @foreach($errors->messages() as $name => $messages)
                @foreach($messages as $message)
                    <li>
                        @if($interactive)
                            <a href="#field-{{ preg_replace('/[^a-zA-Z0-9_-]/', '-', $name) }}"
                                data-error-field="{{ $name }}">{{ $message }}</a>
                        @else
                            {{ $message }}
                        @endif
                    </li>
                @endforeach
            @endforeach
        </ul>
    </div>
@endif
