@extends('layouts.admin')
@section('title', 'Báo cáo')
@section('content')
    <x-admin.page-heading title="Báo cáo khách hàng"
        description="Theo dõi lượng khách, nguồn yêu cầu và kết quả chăm sóc theo khoảng thời gian.">
        <a class="button" href="{{ route('admin.reports.export') }}">
            Xuất CSV tất cả khách<x-admin.icon name="external" />
        </a>
    </x-admin.page-heading>
    <form id="table-filters" class="table-toolbar" method="get" action="{{ route('admin.reports.index') }}">
        <input type="hidden" name="per_page" value="{{ $daily->perPage() }}">
        <div class="table-toolbar-copy">
            <strong>Bộ lọc báo cáo</strong>
            <span>Khoảng ngày áp dụng cho toàn bộ báo cáo; số khách chỉ lọc bảng dữ liệu chi tiết.</span>
        </div>
        <div class="actions">
            <a class="button text-button" href="#daily-report">Xem xu hướng theo ngày</a>
            <button type="submit" class="secondary">Áp dụng bộ lọc</button>
            @if(request()->hasAny(['from', 'to', 'total_min', 'total_max']))
                <a class="button text-button" href="{{ route('admin.reports.index') }}">Xóa bộ lọc</a>
            @endif
        </div>
        @foreach(['sort', 'direction'] as $sortParameter)
            @if(request()->filled($sortParameter))
                <input type="hidden" name="{{ $sortParameter }}" value="{{ request($sortParameter) }}">
            @endif
        @endforeach
        <details class="table-filter-options">
            <summary>Tìm kiếm và bộ lọc</summary>
            <div class="table-filter-grid">
                <x-field name="from" label="Từ ngày" type="date" :value="request('from')"
                    :use-old="false" form="table-filters" />
                <x-field name="to" label="Đến ngày" type="date" :value="request('to')"
                    :use-old="false" form="table-filters" />
                <p class="field-hint">Khoảng ngày cũng cập nhật các thống kê ở trên.</p>

                <x-field name="total_min" label="Ít nhất" type="number" min="0" :value="request('total_min')"
                    :use-old="false" form="table-filters" />
                <x-field name="total_max" label="Nhiều nhất" type="number" min="0" :value="request('total_max')"
                    :use-old="false" form="table-filters" />

            </div>
            <button type="submit" class="secondary">Áp dụng bộ lọc</button>
        </details>
    </form>
    <div class="grid gap-4 sm:grid-cols-3">
        <x-admin.stat-card label="Tổng khách" :value="number_format($total, 0, ',', '.')" icon="users" tone="info" />
        <x-admin.stat-card label="Chốt thành công" :value="number_format($won, 0, ',', '.')"
            icon="check" tone="success" />
        <x-admin.stat-card label="Tỷ lệ chuyển đổi" :value="$conversion.'%'" icon="chart" tone="violet" />
    </div>
    <div class="report-breakdowns">
        <section class="panel">
            <h2>Nguồn khách</h2>
            <div class="report-list" role="region" aria-label="Danh sách nguồn khách" tabindex="0">
                <dl>
                    @forelse($sources as $row)
                        <div class="flex justify-between gap-4 py-3">
                            <dt>{{ $row->source ?: 'Chưa xác định nguồn' }}</dt>
                            <dd>{{ number_format($row->total, 0, ',', '.') }}</dd>
                        </div>
                    @empty
                        <x-admin.empty-state title="Chưa có dữ liệu trong khoảng lọc" />
                    @endforelse
                </dl>
            </div>
        </section>
        <section class="panel">
            <h2>Mẫu xe quan tâm</h2>
            <div class="report-list" role="region" aria-label="Danh sách mẫu xe quan tâm" tabindex="0">
                <dl>
                    @forelse($vehicles as $row)
                        <div class="flex justify-between gap-4 py-3">
                            <dt>{{ $row->vehicle?->name ?? 'Chưa chọn xe' }}</dt>
                            <dd>{{ number_format($row->total, 0, ',', '.') }}</dd>
                        </div>
                    @empty
                        <x-admin.empty-state title="Chưa có dữ liệu trong khoảng lọc" />
                    @endforelse
                </dl>
            </div>
        </section>
    </div>
    <section class="panel">
        <h2>Trạng thái chăm sóc</h2>
        <dl class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
            @forelse($statuses as $row)
                @php
                    $statusTone = match ($row->status) {
                        App\Enums\LeadStatus::Won, App\Enums\LeadStatus::Completed => 'success',
                        App\Enums\LeadStatus::Contacted, App\Enums\LeadStatus::Confirmed => 'info',
                        App\Enums\LeadStatus::New => 'warning',
                        default => 'muted',
                    };
                @endphp
                <div class="report-status" data-tone="{{ $statusTone }}">
                    <dt>{{ __('studio.status.'.$row->status->value) }}</dt>
                    <dd class="shrink-0 font-bold tabular-nums">{{ number_format($row->total, 0, ',', '.') }}</dd>
                </div>
            @empty
                <x-admin.empty-state title="Chưa có dữ liệu trong khoảng lọc" />
            @endforelse
        </dl>
    </section>
    <section class="panel" id="daily-report" aria-labelledby="daily-report-title">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 id="daily-report-title">Khách theo ngày</h2>
                <p class="text-sm text-muted">Số khách mới trong khoảng ngày đã chọn, bao gồm ngày không có khách.</p>
            </div>
            <span class="badge border-sky-100 bg-sky-50 text-sky-900">{{ $total }} khách</span>
        </div>
        <x-admin.daily-lead-chart :rows="$chartDaily" :from="request('from')" :to="request('to')" />
    </section>
    <details class="mt-6">
        <summary class="cursor-pointer text-sm font-semibold text-ink">Xem dữ liệu chi tiết theo ngày</summary>
        <p class="text-sm text-muted">
            {{ number_format($daily->total(), 0, ',', '.') }} ngày có dữ liệu phù hợp.
            Bộ lọc số khách chỉ áp dụng cho bảng này.
        </p>
        <x-admin.table-block :records="$daily" class="report-table">
            <x-admin.table caption="Khách theo ngày">
                <thead>
                    <tr>
                        <x-admin.sortable-column label="Ngày" column="day" default-sort="day" default-direction="desc" />
                        <x-admin.sortable-column label="Số khách" column="total"
                            default-sort="day" default-direction="desc" />
                    </tr>
                </thead>
                <tbody>
                    @forelse($daily as $row)
                        <tr><td>{{ $row->day }}</td><td class="tabular-nums">{{ $row->total }}</td></tr>
                    @empty
                        <tr><td colspan="2"><x-admin.empty-state title="Chưa có dữ liệu trong khoảng lọc" /></td></tr>
                    @endforelse
                </tbody>
            </x-admin.table>
        </x-admin.table-block>
    </details>
@endsection
