@extends('layouts.admin')
@section('title', 'Nhật ký thao tác')
@section('content')
    <x-admin.page-heading title="Nhật ký thao tác"
        description="Nhật ký ghi hành động và tên trường thay đổi, không lưu giá trị dữ liệu khách hàng." />
    <form id="table-filters" class="table-toolbar" method="get" action="{{ route('admin.audit.index') }}">
        <input type="hidden" name="per_page" value="{{ $logs->perPage() }}">
        <div class="table-toolbar-copy">
            <strong>{{ number_format($logs->total(), 0, ',', '.') }} kết quả</strong>
            <span>Lọc theo thời gian, người thực hiện hoặc nội dung thao tác.</span>
        </div>
        <div class="actions">
            <button type="submit" class="secondary">Áp dụng bộ lọc</button>
            @if(request()->hasAny(['from', 'to', 'user_id', 'action', 'subject_type', 'subject_id', 'changed_field']))
                <a class="button text-button" href="{{ route('admin.audit.index') }}">Xóa bộ lọc</a>
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

                <x-select name="user_id" label="Người thực hiện" form="table-filters">
                    <option value="">Tất cả người thực hiện</option>
                    <option value="system" @selected(request('user_id') === 'system')>Hệ thống / khách</option>
                    @foreach($actors as $actor)
                        <option
                            value="{{ $actor->id }}"
                            @selected((string) request('user_id') === (string) $actor->id)
                        >
                            {{ $actor->name }}
                        </option>
                    @endforeach
                </x-select>

                <x-field name="action" label="Lọc hành động" :value="request('action')"
                    :use-old="false" form="table-filters" />

                <x-select name="subject_type" label="Loại đối tượng" form="table-filters">
                    <option value="">Tất cả đối tượng</option>
                    @foreach($subjectTypes as $subjectType)
                        <option value="{{ $subjectType }}" @selected(request('subject_type') === $subjectType)>
                            {{ class_basename($subjectType) }}
                        </option>
                    @endforeach
                </x-select>
                <x-field name="subject_id" label="ID đối tượng" type="number" min="0"
                    :value="request('subject_id')" :use-old="false" form="table-filters" />

                <x-field name="changed_field" label="Tên trường" :value="request('changed_field')"
                    hint="Nhập tên trường đầy đủ, ví dụ title hoặc status."
                    :use-old="false" form="table-filters" />

            </div>
            <button type="submit" class="secondary">Áp dụng bộ lọc</button>
        </details>
    </form>
    <x-admin.table-block :records="$logs">
        <x-admin.table caption="Nhật ký thao tác">
            <thead>
                <tr>
                    <x-admin.sortable-column label="ID" column="id"
                        default-sort="created_at" default-direction="desc" />
                    <x-admin.sortable-column label="Người thực hiện" column="actor"
                        default-sort="created_at" default-direction="desc" />
                    <x-admin.sortable-column label="Hành động" column="action"
                        default-sort="created_at" default-direction="desc" />
                    <x-admin.sortable-column label="Đối tượng" column="subject"
                        default-sort="created_at" default-direction="desc" />
                    <x-admin.sortable-column label="Trường thay đổi" column="changed_fields"
                        default-sort="created_at" default-direction="desc" />
                <x-admin.sortable-column label="Ngày tạo" column="created_at"
                        default-sort="created_at" default-direction="desc" />
                    <x-admin.sortable-column label="Ngày cập nhật" column="updated_at"
                        default-sort="created_at" default-direction="desc" />
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                    <tr>
                        <td class="table-secondary table-id">#{{ $log->id }}</td>
                        <td>
                            {{ $log->actor?->name ?? 'Hệ thống / khách' }}
                        </td>
                        <td>
                            {{ $log->action }}
                        </td>
                        <td>
                            {{ class_basename($log->subject_type) }}
                            #
                            {{ $log->subject_id }}
                        </td>
                        <td>
                            {{ implode(', ', $log->changed_fields) }}
                        </td>
                    <x-admin.record-dates :record="$log" />
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">
                            <x-admin.empty-state title="Chưa có nhật ký thao tác"
                                description="Thử điều chỉnh bộ lọc hoặc quay lại khi có thao tác mới." />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </x-admin.table>
    </x-admin.table-block>
@endsection
