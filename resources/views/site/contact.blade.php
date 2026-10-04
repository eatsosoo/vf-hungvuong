@extends('layouts.site')
@section('title', __('Liên hệ & đăng ký lái thử · VinFast Hùng Vương'))
@section('content')
    <p class="eyebrow">
        {{ __('BẮT ĐẦU HÀNH TRÌNH') }}
    </p>
    <h1>
        {{ __('Chúng tôi đồng hành cùng bạn.') }}
    </h1>
    <div class="two-columns">
        <section class="panel">
            <h2>
                {{ __('Thông tin đại lý') }}
            </h2>
            <p>
                {{ $siteSettings['address'] ?? __('Địa chỉ đang được cập nhật.') }}
            </p>
            <p>
                {{ $siteSettings['opening_hours'] ?? '' }}
            </p>
            @if(! empty($siteSettings['hotline']))
                <p>
                    <a href="tel:{{ $siteSettings['hotline'] }}">
                        {{ __('Gọi') }}
                        {{ $siteSettings['hotline'] }}
                    </a>
                </p>
            @endif
            @if(! empty($siteSettings['map_url']))
                <a href="{{ $siteSettings['map_url'] }}" rel="noopener noreferrer">
                    {{ __('Xem bản đồ ↗') }}
                </a>
            @endif
            <p>
                {{ __('Gửi thời gian mong muốn. Nhân viên sẽ liên hệ để xác nhận lịch và địa điểm.') }}
            </p>
        </section>
        <form class="panel" method="post" action="{{ route($clientRoutePrefix.'leads.store') }}">
            @csrf
            <label class="field">
                {{ __('Bạn cần hỗ trợ') }}
                <select name="type" id="leadType">
                    @foreach(['consultation' => 'Tư vấn chọn xe',
                        'quote' => 'Nhận báo giá',
                        'test_drive' => 'Đăng ký lái thử'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('type', request('type', 'consultation')) === $value)>
                            {{ __($label) }}
                        </option>
                    @endforeach
                </select>
            </label>
            <x-field name="name" label="{{ __('Họ và tên') }}" required />
            <x-field name="phone" label="{{ __('Số điện thoại') }}" type="tel" required />
            <x-field name="email" label="{{ __('Email (tùy chọn)') }}" type="email" />
            <label class="field">
                {{ __('Xe quan tâm') }}
                <select name="vehicle_id" id="leadVehicle">
                    <option value="">
                        {{ __('Chưa chọn') }}
                    </option>
                    @foreach($vehicles as $vehicle)
                        <option
                            value="{{ $vehicle->id }}"
                            @selected(old('vehicle_id', request('vehicle')) == $vehicle->id)
                        >
                            {{ $vehicle->name }}
                        </option>
                    @endforeach
                </select>
            </label>
            <label class="field">
                {{ __('Phiên bản') }}
                <select name="vehicle_variant_id" id="leadVariant">
                    <option value="">
                        {{ __('Chưa chọn') }}
                    </option>
                    @foreach($vehicles as $vehicle)
                        @foreach($vehicle->variants as $variant)
                            <option
                                value="{{ $variant->id }}"
                                data-vehicle="{{ $vehicle->id }}"
                                @selected(old('vehicle_variant_id', request('variant')) == $variant->id)
                            >
                                {{ $vehicle->name }}
                                ·
                                {{ $variant->name }}
                            </option>
                        @endforeach
                    @endforeach
                </select>
            </label>
            <div id="appointmentField">
                <x-field name="preferred_at"
                    label="{{ __('Lịch lái thử mong muốn (giờ Việt Nam)') }}" type="datetime-local" />
            </div>
            <x-field name="message" label="{{ __('Nhu cầu của bạn') }}" type="textarea" />
            <div class="honeypot" aria-hidden="true">
                <label>
                    Website
                    <input name="website" tabindex="-1" autocomplete="off">
                </label>
            </div>
            <label class="consent">
                <input type="checkbox" name="consent" value="1" required @checked(old('consent'))>
                {{ __('Tôi đồng ý cho đại lý liên hệ để xử lý yêu cầu theo') }}
                <a href="{{ route($clientRoutePrefix.'pages.show', 'bao-mat') }}">
                    {{ __('chính sách bảo mật') }}
                </a>
                .
            </label>
            <button>
                {{ __('Gửi yêu cầu') }}
            </button>
        </form>
    </div>
@endsection
