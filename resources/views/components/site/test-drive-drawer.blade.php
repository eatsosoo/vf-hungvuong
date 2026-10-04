@props(['vehicles', 'contextVehicleId' => null])
<dialog
    id="test-drive-drawer"
    class="test-drive-drawer"
    data-test-drive-drawer
    data-context-vehicle="{{ $contextVehicleId }}"
    aria-labelledby="test-drive-title"
    aria-describedby="test-drive-description"
>
    <div class="test-drive-shell">
        <header class="test-drive-header">
            <button type="button" class="test-drive-close" data-test-drive-close
                aria-label="{{ __('Đóng đăng ký lái thử') }}">
                <x-admin.icon name="close" />
            </button>
            <h2 id="test-drive-title">{{ __('Đặt lịch lái thử') }}</h2>
            <p id="test-drive-description" class="sr-only">
                {{ __('Chọn xe và thời gian phù hợp. Đại lý sẽ liên hệ để xác nhận lịch lái thử.') }}
            </p>
        </header>
        <form id="test-drive-form" class="test-drive-form" data-test-drive-form
            method="post" action="{{ route($clientRoutePrefix.'leads.store') }}">
            @csrf
            <input type="hidden" name="type" value="test_drive">
            <input type="hidden" name="source" value="test-drive-drawer">
            <div class="test-drive-body">
                <div class="test-drive-feedback" data-test-drive-feedback role="alert" tabindex="-1" hidden></div>
                <section class="test-drive-vehicle-summary" data-test-drive-vehicle-summary
                    aria-label="{{ __('Xe đăng ký lái thử') }}" hidden>
                    <div class="test-drive-vehicle-visual">
                        <img data-test-drive-vehicle-image alt="" width="88" height="64" hidden>
                        <span class="test-drive-vehicle-fallback" data-test-drive-vehicle-fallback>
                            <x-admin.icon name="car" />
                        </span>
                    </div>
                    <div class="test-drive-vehicle-copy">
                        <strong data-test-drive-vehicle-name></strong>
                        <p data-test-drive-vehicle-detail></p>
                    </div>
                    <button type="button" class="test-drive-change-vehicle" data-test-drive-change-vehicle
                        aria-expanded="false" aria-controls="test-drive-vehicle-fields">
                        {{ __('Đổi xe') }}
                    </button>
                </section>
                <div class="test-drive-fields">
                    <div id="test-drive-vehicle-fields" class="test-drive-row" data-test-drive-vehicle-fields>
                        <x-select name="vehicle_id" label="{{ __('Mẫu xe quan tâm') }}" id="test-drive-vehicle">
                            <option value="">{{ __('Chưa chọn mẫu xe') }}</option>
                            @foreach($vehicles as $vehicle)
                                <option
                                    value="{{ $vehicle->id }}"
                                    data-model="{{ $vehicle->name }}"
                                    data-image="{{ $vehicle->media?->url() }}"
                                    data-segment="{{ $vehicle->segment }}"
                                >
                                    {{ $vehicle->name }}
                                </option>
                            @endforeach
                        </x-select>
                        <x-select name="vehicle_variant_id" label="{{ __('Phiên bản') }}" id="test-drive-variant">
                            <option value="">{{ __('Chưa chọn phiên bản') }}</option>
                            @foreach($vehicles as $vehicle)
                                @foreach($vehicle->variants as $variant)
                                    <option value="{{ $variant->id }}" data-vehicle="{{ $vehicle->id }}"
                                        data-name="{{ $variant->name }}">
                                        {{ $variant->name }}
                                    </option>
                                @endforeach
                            @endforeach
                        </x-select>
                    </div>
                    <div class="test-drive-row">
                        <x-field name="name" label="{{ __('Họ và tên') }}" id="test-drive-name"
                            autocomplete="name" maxlength="100" :use-old="false" required />
                        <x-field name="phone" label="{{ __('Số điện thoại') }}" id="test-drive-phone" type="tel"
                            autocomplete="tel" maxlength="20" :use-old="false" required />
                    </div>
                    <x-field
                        name="preferred_at"
                        label="{{ __('Ngày và giờ mong muốn') }}"
                        id="test-drive-preferred-at"
                        type="datetime-local"
                        hint="Giờ Việt Nam · Lịch sẽ được xác nhận qua điện thoại."
                        :use-old="false"
                        required
                    />
                    <x-field name="email" label="{{ __('Email (tùy chọn)') }}" id="test-drive-email" type="email"
                        autocomplete="email" maxlength="254" :use-old="false" />
                    <x-field name="message"
                        label="{{ __('Ghi chú (tùy chọn)') }}"
                        id="test-drive-message" type="textarea"
                        placeholder="{{ __('Chia sẻ thêm nhu cầu của bạn…') }}" maxlength="2000" :rows="3"
                        :use-old="false" />
                    <div class="test-drive-honeypot" aria-hidden="true">
                        <label for="test-drive-website">Website</label>
                        <input id="test-drive-website" name="website" tabindex="-1" autocomplete="off">
                    </div>
                    <div class="field">
                        <label class="test-drive-consent" for="test-drive-consent">
                            <input id="test-drive-consent" type="checkbox" name="consent" value="1" required>
                            <span>
                                {{ __('Tôi đồng ý cho đại lý liên hệ để xác nhận lịch theo') }}
                                <a
                                    href="{{ route($clientRoutePrefix.'pages.show', 'bao-mat') }}">
                                    {{ __('chính sách bảo mật') }}
                                </a>.
                            </span>
                        </label>
                    </div>
                </div>
            </div>
            <div class="test-drive-footer">
                <button type="submit" class="test-drive-submit" data-test-drive-submit>
                    <span>{{ __('Gửi đăng ký lái thử') }}</span>
                    <x-admin.icon name="arrow" />
                </button>
                <p>{{ __('Đăng ký miễn phí · Tư vấn theo nhu cầu của bạn') }}</p>
            </div>
        </form>
        <section class="test-drive-success" data-test-drive-success tabindex="-1" hidden>
            <span class="test-drive-success-icon"><x-admin.icon name="check" /></span>
            <h3>{{ __('Đã nhận đăng ký của bạn') }}</h3>
            <p data-test-drive-success-message>
                {{ __('Đội ngũ VinFast Hùng Vương sẽ liên hệ để xác nhận lịch và địa điểm lái thử.') }}
            </p>
            <button type="button" class="test-drive-submit" data-test-drive-close>
                {{ __('Tiếp tục khám phá') }}
            </button>
        </section>
    </div>
</dialog>
