@extends('layouts.admin')
@section('title', 'Khách hàng & yêu cầu')
@section('content')
    <x-admin.page-heading :title="request('type') === 'test_drive' ? 'Lịch lái thử' : 'Khách hàng & yêu cầu'">
        @if(request('type') === 'test_drive')
            <nav class="actions" aria-label="Chế độ hiển thị lịch lái thử">
                <a class="button secondary"
                    href="{{ route('admin.leads.index', array_merge(
                        request()->except('page'), ['view' => 'calendar']
                    )) }}">
                    <x-admin.icon name="calendar" /> Lịch tháng
                </a>
                <a class="button" aria-current="page"
                    href="{{ route('admin.leads.index', array_merge(request()->except('page'), ['view' => 'list'])) }}">
                    <x-admin.icon name="menu" /> Danh sách
                </a>
            </nav>
        @endif
    </x-admin.page-heading>
    <form id="table-filters" class="table-toolbar" method="get" action="{{ route('admin.leads.index') }}">
        <input type="hidden" name="view" value="list">
        <input type="hidden" name="per_page" value="{{ $leads->perPage() }}">
        <div class="table-toolbar-copy">
            <strong>{{ number_format($leads->total(), 0, ',', '.') }} yêu cầu</strong>
            <p>Nhấn tiêu đề cột để sắp xếp tăng hoặc giảm dần.</p>
        </div>
        @if(request()->except('view', 'page', 'per_page', 'sort', 'direction'))
            <a class="button secondary" href="{{ route('admin.leads.index', ['view' => 'list']) }}">
                Xóa bộ lọc
            </a>
        @endif
        @foreach(['sort', 'direction'] as $sortParameter)
            @if(request()->filled($sortParameter))
                <input type="hidden" name="{{ $sortParameter }}" value="{{ request($sortParameter) }}">
            @endif
        @endforeach
        <details class="table-filter-options">
            <summary>Tìm kiếm và bộ lọc</summary>
            <div class="table-filter-grid">
                <x-field name="q" label="Tìm tên khách hàng" :value="request('q')"
                    :use-old="false" form="table-filters" />

                <x-select name="type" label="Loại yêu cầu" form="table-filters">
                    <option value="">Tất cả yêu cầu</option>
                    @foreach(['consultation' => 'Tư vấn', 'quote' => 'Báo giá',
                        'test_drive' => 'Lái thử'] as $value => $label)
                        <option value="{{ $value }}" @selected(request('type') === $value)>
                            {{ $label }}
                        </option>
                    @endforeach
                </x-select>

                <x-select name="vehicle_id" label="Mẫu xe" form="table-filters">
                    <option value="">Tất cả xe</option>
                    @foreach($vehicles as $vehicle)
                        <option value="{{ $vehicle->id }}" @selected(request('vehicle_id') == $vehicle->id)>
                            {{ $vehicle->name }}
                        </option>
                    @endforeach
                </x-select>

                <x-select name="assigned_to" label="Người phụ trách" form="table-filters">
                    <option value="">Tất cả</option>
                    <option value="unassigned" @selected(request('assigned_to') === 'unassigned')>
                        Chưa phân công
                    </option>
                    @foreach($staff as $member)
                        <option value="{{ $member->id }}" @selected(request('assigned_to') == $member->id)>
                            {{ $member->name }}
                        </option>
                    @endforeach
                </x-select>

                <x-select name="status" label="Trạng thái yêu cầu" form="table-filters">
                    <option value="">Tất cả trạng thái</option>
                    @foreach(App\Enums\LeadStatus::cases() as $status)
                        <option value="{{ $status->value }}" @selected(request('status') === $status->value)>
                            {{ __('studio.status.'.$status->value) }}
                        </option>
                    @endforeach
                </x-select>

                <x-field name="date_from" label="Từ ngày" type="date" :value="request('date_from')"
                    :use-old="false" form="table-filters" />
                <x-field name="date_to" label="Đến ngày" type="date" :value="request('date_to')"
                    :use-old="false" form="table-filters" />

            </div>
            <button type="submit" class="secondary">Áp dụng bộ lọc</button>
        </details>
    </form>
    <x-admin.table-block :records="$leads">
        <x-admin.table caption="Khách hàng và yêu cầu">
            <thead>
                <tr>
                    <x-admin.sortable-column label="ID" column="id"
                        default-sort="created_at" default-direction="desc" />
                    <x-admin.sortable-column label="Khách hàng" column="name" />
                    <x-admin.sortable-column label="Yêu cầu" column="type" />
                    <x-admin.sortable-column label="Xe" column="vehicle" />
                    <x-admin.sortable-column label="Phụ trách" column="assignee" />
                    <x-admin.sortable-column label="Trạng thái" column="status" />
                    <x-admin.sortable-column label="Thời gian" column="time" />
                    <x-admin.sortable-column label="Ngày tạo" column="created_at"
                        default-sort="created_at" default-direction="desc" />
                    <x-admin.sortable-column label="Ngày cập nhật" column="updated_at"
                        default-sort="created_at" default-direction="desc" />
                    <th scope="col" class="table-actions-column">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse($leads as $lead)
                    <tr>
                        <td class="table-secondary table-id">#{{ $lead->id }}</td>
                        <td>
                            <span class="table-title">{{ $lead->name }}</span>
                            <small>
                                {{ $lead->phone }}
                            </small>
                        </td>
                        <td>
                            {{ __('studio.type.'.$lead->type) }}
                        </td>
                        <td>
                            {{ $lead->vehicle?->name }}
                        </td>
                        <td>
                            {{ $lead->assignee?->name ?? 'Chưa phân công' }}
                        </td>
                        <td>
                            <x-admin.badge :tone="match ($lead->status->value) {
                                'won', 'completed' => 'success', 'lost', 'cancelled' => 'danger',
                                'new' => 'warning', default => 'info',
                                }">{{ __('studio.status.'.$lead->status->value) }}</x-admin.badge>
                        </td>
                        <td>
                            {{ ($lead->appointment_at ?? $lead->preferred_at ?? $lead->created_at)
                            ->format('d/m/Y H:i') }}
                        </td>
                        <x-admin.record-dates :record="$lead" />
                        <td class="table-actions-column">
                            <div class="table-row-actions">
                                <a class="table-action" href="{{ route('admin.leads.edit', $lead) }}"
                                    aria-label="Xử lý #{{ $lead->id }}" title="Xử lý">
                                    <x-admin.icon name="edit" />
                                </a>

                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10">
                            <x-admin.empty-state
                                title="Chưa có yêu cầu phù hợp"
                                description="Thử thay đổi bộ lọc hoặc quay lại khi có yêu cầu mới."
                            />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </x-admin.table>
    </x-admin.table-block>
@endsection
