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
            <span>Khoảng ngày áp dụng cho toàn bộ báo cáo; số khách chỉ lọc bảng theo ngày.</span>
        </div>
        <div class="actions">
            <a class="button text-button" href="#daily-report">Lọc khách theo ngày</a>
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
        <x-admin.stat-card label="Tổng khách" :value="$total" icon="users" />
        <x-admin.stat-card label="Chốt thành công" :value="$won" icon="check" />
        <x-admin.stat-card label="Tỷ lệ chuyển đổi" :value="$conversion.'%'" icon="chart" />
    </div>
    <div class="two-columns">
        <section class="panel">
            <h2>Nguồn khách</h2>
            <dl class="divide-y divide-line">
                @forelse($sources as $row)
                    <div class="flex justify-between gap-4 py-3">
                        <dt>{{ $row->source }}</dt><dd class="font-bold tabular-nums">{{ $row->total }}</dd>
                    </div>
                @empty
                    <x-admin.empty-state title="Chưa có dữ liệu trong khoảng lọc" />
                @endforelse
            </dl>
        </section>
        <section class="panel">
            <h2>Mẫu xe quan tâm</h2>
            <dl class="divide-y divide-line">
                @forelse($vehicles as $row)
                    <div class="flex justify-between gap-4 py-3">
                        <dt>{{ $row->vehicle?->name ?? 'Chưa chọn xe' }}</dt>
                        <dd class="font-bold tabular-nums">{{ $row->total }}</dd>
                    </div>
                @empty
                    <x-admin.empty-state title="Chưa có dữ liệu trong khoảng lọc" />
                @endforelse
            </dl>
        </section>
    </div>
    <section class="panel">
        <h2>Trạng thái chăm sóc</h2>
        <dl class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
            @forelse($statuses as $row)
                <div class="flex justify-between gap-4 rounded-xl bg-paper p-4">
                    <dt>{{ __('studio.status.'.$row->status->value) }}</dt>
                    <dd class="font-bold tabular-nums">{{ $row->total }}</dd>
                </div>
            @empty
                <x-admin.empty-state title="Chưa có dữ liệu trong khoảng lọc" />
            @endforelse
        </dl>
    </section>
    <h2 id="daily-report">Khách theo ngày</h2>
    <p class="text-sm text-muted">
        {{ number_format($daily->total(), 0, ',', '.') }} ngày có dữ liệu phù hợp.
        Bộ lọc số khách chỉ áp dụng cho bảng này.
    </p>
    <x-admin.table-block :records="$daily">
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
@endsection
