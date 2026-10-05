@extends('layouts.site')
@section('full_width', 'true')
@section('content')
    <section class="bg-night text-white" aria-labelledby="comparison-title">
        <div class="client-container pt-6 pb-16 sm:pt-8 sm:pb-20">
            <x-site.breadcrumbs :items="$breadcrumbs" dark />
            <p class="client-eyebrow mt-8 text-brand">{{ __('Cùng chọn chiếc xe phù hợp') }}</p>
            <h1 id="comparison-title" class="mt-4">{{ __('So sánh xe') }}</h1>
            <p class="mt-5 max-w-2xl leading-7 text-white/75">
                {{ __('Đặt hai mẫu xe cạnh nhau để xem giá tham khảo, phiên bản và thông số.') }}
            </p>
        </div>
    </section>
    <div class="client-container pb-12 sm:pb-16">
        <form class="vehicle-comparison-form" method="get" action="{{ route($clientRoutePrefix.'vehicles.compare') }}">
            <div class="grid gap-5 sm:grid-cols-2">
                @foreach([
                    'first' => __('Mẫu xe thứ nhất'), 'second' => __('Mẫu xe thứ hai'),
                ] as $field => $label)
                    @php($selectedVehicle = $field === 'first' ? $firstVehicle : $secondVehicle)
                    <x-select :name="$field" :label="$label" required>
                        <option value="">{{ __('Chọn mẫu xe') }}</option>
                        @foreach($comparisonOptions as $option)
                            <option value="{{ $option->slug }}"
                                @selected(old($field, $selectedVehicle?->slug) === $option->slug)>
                                {{ $option->name }}
                            </option>
                        @endforeach
                    </x-select>
                @endforeach
            </div>
            <div class="mt-6 flex flex-wrap items-center justify-between gap-4">
                <p class="text-sm text-muted">{{ __('Chọn hai mẫu xe khác nhau để bắt đầu.') }}</p>
                <button type="submit" class="client-button" @disabled($comparisonOptions->count() < 2)>
                    {{ __('So sánh xe') }} <x-admin.icon name="arrow" class="size-4" />
                </button>
            </div>
            @if($comparisonOptions->count() < 2)
                <p class="mt-4 text-sm text-muted" role="status">
                    {{ __('Cần ít nhất hai mẫu xe đang được hiển thị để so sánh.') }}
                </p>
            @endif
        </form>
        @if($firstVehicle && $secondVehicle)
            <section class="mt-10" aria-labelledby="comparison-specifications-title">
                <div class="mb-5 flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <p class="client-eyebrow">{{ __('Tìm hiểu chi tiết') }}</p>
                        <h2 id="comparison-specifications-title" class="mt-3">{{ __('Thông số kỹ thuật') }}</h2>
                    </div>
                    <p class="flex items-center gap-2 text-sm text-muted">
                        <span class="size-3 shrink-0 rounded-full bg-brand" aria-hidden="true"></span>
                        {{ __('Các mục khác nhau được làm nổi bật.') }}
                    </p>
                </div>
                <p class="mb-3 text-sm text-muted md:hidden">
                    {{ __('Vuốt ngang để xem đủ hai mẫu xe.') }}
                </p>
                <div class="vehicle-comparison-table-wrapper" role="region" tabindex="0"
                    aria-label="{{ __('Bảng so sánh xe, có thể cuộn ngang trên màn hình nhỏ') }}">
                    <table class="vehicle-comparison-table">
                        <caption class="sr-only">
                            {{ __('So sánh :first và :second', [
                                'first' => $firstVehicle->name, 'second' => $secondVehicle->name,
                            ]) }}
                        </caption>
                        <thead>
                            <tr>
                                <th scope="col">{{ __('Thông tin') }}</th>
                                <th scope="col">{{ $firstVehicle->name }}</th>
                                <th scope="col">{{ $secondVehicle->name }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <th scope="row">{{ __('Phiên bản & giá tham khảo') }}</th>
                                @foreach([$firstVehicle, $secondVehicle] as $selectedVehicle)
                                    <td>
                                        <ul class="space-y-4">
                                            @forelse($selectedVehicle->variants as $variant)
                                                <li>
                                                    <p class="font-semibold">{{ $variant->name }}</p>
                                                    <p class="mt-1 text-sm text-muted">
                                                        {{ $variant->price !== null
                                                            ? number_format((float) $variant->price, 0, ',', '.')
                                                                .' '.__('VNĐ') : __('Liên hệ nhận báo giá') }}
                                                    </p>
                                                </li>
                                            @empty
                                                <li class="text-muted">{{ __('Chưa cập nhật') }}</li>
                                            @endforelse
                                        </ul>
                                    </td>
                                @endforeach
                            </tr>
                            @forelse($comparisonRows as $row)
                                <tr data-different="{{ $row['different'] ? 'true' : 'false' }}">
                                    <th scope="row">
                                        {{ $row['label'] }}
                                        @if($row['different'])
                                            <span class="sr-only"> — {{ __('Khác nhau') }}</span>
                                        @endif
                                    </th>
                                    @foreach(['first', 'second'] as $side)
                                        <td @class(['text-muted' => $row[$side] === null])>
                                            {{ $row[$side] ?? __('Chưa cập nhật') }}
                                        </td>
                                    @endforeach
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-muted">
                                        {{ __('Thông số của hai mẫu xe đang được cập nhật.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <p class="mt-5 text-sm leading-6 text-muted">
                    {{ __('Giá và thông số tùy theo phiên bản. Đại lý sẽ xác nhận thông tin tại thời điểm tư vấn.') }}
                </p>
            </section>
        @else
            <p class="mt-8 text-center text-sm leading-6 text-muted" role="status">
                {{ __('Bảng so sánh sẽ xuất hiện khi bạn chọn đủ hai mẫu xe.') }}
            </p>
        @endif
        <div class="mt-8 grid grid-cols-2 gap-3 sm:gap-6">
            @foreach([$firstVehicle, $secondVehicle] as $selectedVehicle)
                <article class="vehicle-comparison-card">
                    <div class="vehicle-comparison-image">
                        @if($selectedVehicle?->media)
                            <img
                                src="{{ $selectedVehicle->media->url() }}"
                                alt="{{ $selectedVehicle->media->alt ?: $selectedVehicle->name }}"
                                width="{{ $selectedVehicle->media->width ?: 900 }}"
                                height="{{ $selectedVehicle->media->height ?: 600 }}"
                                decoding="async"
                                class="h-full w-full object-contain"
                            >
                        @else
                            <x-admin.icon name="car" class="size-16 text-muted/40" />
                        @endif
                    </div>
                    <div class="flex flex-1 flex-col p-4 sm:p-6">
                        @if($selectedVehicle)
                            @php($price = $selectedVehicle->variants->whereNotNull('price')->min('price'))
                            <p class="client-eyebrow">{{ $selectedVehicle->segment ?: __('Dòng xe VinFast') }}</p>
                            <h2 class="vehicle-comparison-name">{{ $selectedVehicle->name }}</h2>
                            <p class="mt-4 text-xs text-muted">
                                {{ $price !== null
                                    ? __('Giá tham khảo từ') : __('Giá và ưu đãi hiện hành') }}
                            </p>
                            <p class="mt-1 text-base font-bold sm:text-xl">
                                {{ $price !== null
                                    ? number_format((float) $price, 0, ',', '.').' '.__('VNĐ')
                                    : __('Liên hệ nhận báo giá') }}
                            </p>
                            <a
                                class="mt-4 inline-flex min-h-11 items-center gap-2 text-sm font-semibold text-green-text"
                                href="{{ route($clientRoutePrefix.'vehicles.show', $selectedVehicle->slug) }}">
                                {{ __('Khám phá mẫu xe') }} <x-admin.icon name="arrow" class="size-4 shrink-0" />
                            </a>
                            <a class="client-button mt-auto text-center" data-test-drive-open
                                href="{{ route($clientRoutePrefix.'contact', [
                                    'type' => 'test_drive', 'vehicle' => $selectedVehicle->id,
                                ]) }}">
                                {{ __('Đăng ký lái thử') }}
                            </a>
                        @else
                            <h2 class="vehicle-comparison-name">
                                {{ $loop->first ? __('Mẫu xe thứ nhất') : __('Mẫu xe thứ hai') }}
                            </h2>
                            <p class="mt-3 text-sm leading-6 text-muted">
                                {{ __('Chọn mẫu xe ở phía trên.') }}
                            </p>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
    </div>
@endsection
