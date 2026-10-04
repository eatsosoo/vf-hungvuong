@extends('layouts.admin')
@section('title', 'Khuyến mãi')
@section('content')
    @php
        $formAction = $record->exists
            ? route('admin.promotions.update', $record)
            : route('admin.promotions.store');
        $selectedVehicleIds = session()->hasOldInput('vehicle_selection_present')
            ? old('vehicles', []) : old('vehicles', $selectedVehicles);
        $selectedVehicleIds = is_array($selectedVehicleIds) ? $selectedVehicleIds : [];
        $vehicleErrors = array_merge($errors->get('vehicles'), ...array_values($errors->get('vehicles.*')));
        $vehicleDescription = 'field-vehicles-hint'.($vehicleErrors ? ' field-vehicles-error' : '');
    @endphp
    <x-admin.page-heading
        :title="$record->exists ? 'Chỉnh sửa khuyến mãi' : 'Tạo khuyến mãi'"
        description="Soạn nội dung ưu đãi, chọn xe áp dụng và thiết lập thời gian hiển thị."
    >
        <a class="button secondary" href="{{ route('admin.promotions.index') }}">
            <x-admin.icon name="arrow" class="rotate-180" />Danh sách khuyến mãi
        </a>
    </x-admin.page-heading>
    <form class="promotion-layout" method="post" action="{{ $formAction }}">
        @csrf
        @if($record->exists)
            @method('PUT')
        @endif
        <input type="hidden" name="vehicle_selection_present" value="1">
        <div class="promotion-main">
            <section class="panel form-section" aria-labelledby="promotion-information-title">
                <h2 class="section-heading" id="promotion-information-title">Thông tin chương trình</h2>
                <p class="section-description">
                    Đặt tên rõ ràng để khách hàng dễ nhận biết ưu đãi.
                </p>
                <x-field
                    name="title"
                    label="Tên khuyến mãi"
                    :value="$record->title"
                    maxlength="255"
                    required
                />
                <x-field
                    name="slug"
                    slug-source="title"
                    :slug-auto="! $record->exists"
                    label="Đường dẫn"
                    :value="$record->slug"
                    hint="Viết thường, không dấu, nối từ bằng gạch ngang. Ví dụ: uu-dai-vf-7."
                    maxlength="180"
                    required
                />
            </section>
            <section class="panel form-section" aria-labelledby="promotion-content-title">
                <h2 class="section-heading" id="promotion-content-title">Nội dung ưu đãi</h2>
                <p class="section-description">
                    Trình bày quyền lợi, điều kiện và cách khách hàng nhận ưu đãi.
                </p>
                <x-field
                    name="body"
                    label="Nội dung chương trình"
                    type="textarea"
                    class="promotion-body"
                    :value="$record->body"
                    :rows="12"
                    hint="Hỗ trợ Markdown: ## cho tiêu đề, **chữ đậm** và - cho danh sách."
                    required
                />
            </section>
            <fieldset
                class="panel form-section"
                id="field-vehicles"
                aria-describedby="{{ $vehicleDescription }}"
                aria-invalid="{{ $vehicleErrors ? 'true' : 'false' }}"
            >
                <legend class="section-heading">Xe áp dụng</legend>
                <p class="section-description" id="field-vehicles-hint">
                    Chọn các mẫu xe thuộc chương trình. Có thể chọn nhiều xe.
                </p>
                @if($vehicles->isNotEmpty())
                    <div class="choice-grid">
                        @foreach($vehicles as $vehicle)
                            <label class="choice-card" for="promotion-vehicle-{{ $vehicle->id }}">
                                <input
                                    type="checkbox"
                                    name="vehicles[]"
                                    id="promotion-vehicle-{{ $vehicle->id }}"
                                    value="{{ $vehicle->id }}"
                                    @checked(in_array($vehicle->id, $selectedVehicleIds))
                                    aria-describedby="{{ $vehicleDescription }}"
                                    aria-invalid="{{ $vehicleErrors ? 'true' : 'false' }}"
                                >
                                <span class="choice-copy">
                                    <strong>{{ $vehicle->name }}</strong>
                                    @if($vehicle->segment)
                                        <span>{{ $vehicle->segment }}</span>
                                    @endif
                                </span>
                            </label>
                        @endforeach
                    </div>
                @else
                    <p class="field-hint">
                        Chưa có mẫu xe. <a href="{{ route('admin.vehicles.create') }}">Thêm xe vào danh mục</a>.
                    </p>
                @endif
                @if($vehicleErrors)
                    <div class="error" id="field-vehicles-error">
                        @foreach(array_unique($vehicleErrors) as $message)
                            <p>{{ $message }}</p>
                        @endforeach
                    </div>
                @endif
            </fieldset>
        </div>
        <aside class="promotion-side" aria-label="Thiết lập khuyến mãi">
            <section class="panel form-section" aria-labelledby="promotion-schedule-title">
                <h2 class="section-heading" id="promotion-schedule-title">Thời gian áp dụng</h2>
                <p class="section-description">Ngày và giờ được tính theo giờ Việt Nam.</p>
                <x-field
                    name="starts_at"
                    label="Bắt đầu"
                    type="datetime-local"
                    :value="$record->starts_at?->format('Y-m-d\TH:i')"
                    required
                />
                <x-field
                    name="ends_at"
                    label="Kết thúc"
                    type="datetime-local"
                    :value="$record->ends_at?->format('Y-m-d\TH:i')"
                    hint="Thời gian kết thúc phải sau thời gian bắt đầu."
                    required
                />
            </section>
            <section class="panel form-section" aria-labelledby="promotion-visibility-title">
                <h2 class="section-heading" id="promotion-visibility-title">Hiển thị trên website</h2>
                <p class="section-description">
                    Khuyến mãi chỉ hiển thị trong thời gian áp dụng khi được bật.
                </p>
                <x-select name="is_active" label="Trạng thái hiển thị" required>
                    <option value="0" @selected(! old('is_active', $record->is_active))>Ẩn khuyến mãi</option>
                    <option value="1" @selected(old('is_active', $record->is_active))>Bật hiển thị</option>
                </x-select>
            </section>
            <section class="panel form-section" aria-labelledby="promotion-media-title">
                <h2 class="section-heading" id="promotion-media-title">Ảnh chương trình</h2>
                <p class="section-description">Ảnh đại diện xuất hiện trong danh sách khuyến mãi.</p>
                <x-admin.media-picker
                    name="media_id"
                    label="Ảnh đại diện"
                    :selected="$record->media_id"
                    :media="$media"
                />
            </section>
        </aside>
        <div class="panel form-footer">
            <p class="field-hint">Các trường có dấu <span aria-hidden="true">*</span> là bắt buộc.</p>
            <div class="form-actions">
                <a class="button secondary" href="{{ route('admin.promotions.index') }}">Hủy</a>
                <button type="submit"><x-admin.icon name="check" />Lưu khuyến mãi</button>
            </div>
        </div>
    </form>
@endsection
