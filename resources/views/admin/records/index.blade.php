@extends('layouts.admin')
@section('title', $title)
@section('content')
    <x-admin.page-heading :title="$title" description="Quản lý nội dung và sắp xếp danh sách theo từng cột.">
        <a class="button" href="{{ route('admin.'.$resource.'.create') }}">
            Thêm mới
        </a>
    </x-admin.page-heading>
    <form id="table-filters" class="table-toolbar" method="get" action="{{ route('admin.'.$resource.'.index') }}">
        <input type="hidden" name="per_page" value="{{ $records->perPage() }}">
        <div class="table-toolbar-copy">
            <strong>{{ number_format($records->total(), 0, ',', '.') }} kết quả</strong>
            <span>Nhấn tiêu đề cột để sắp xếp tăng hoặc giảm dần.</span>
        </div>
        <div class="actions">
            <button type="submit" class="secondary">Áp dụng bộ lọc</button>
            @if(request()->hasAny(['q', 'slug', 'is_active']))
                <a class="button text-button" href="{{ route('admin.'.$resource.'.index') }}">Xóa bộ lọc</a>
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
                <x-field name="q" label="Lọc tên" :value="request('q')" :use-old="false" form="table-filters" />

                <x-field name="slug" label="Lọc đường dẫn" :value="request('slug')"
                    :use-old="false" form="table-filters" />

                <x-select name="is_active" label="Trạng thái hiển thị" form="table-filters">
                    <option value="">Tất cả trạng thái</option>
                    <option value="1" @selected((string) request('is_active') === '1')>Đang bật</option>
                    <option value="0" @selected((string) request('is_active') === '0')>Đang ẩn</option>
                </x-select>

            </div>
            <button type="submit" class="secondary">Áp dụng bộ lọc</button>
        </details>
    </form>
    <x-admin.table-block :records="$records">
        <x-admin.table :caption="'Danh sách '.mb_strtolower($title)">
            <thead>
                <tr>
                    <x-admin.sortable-column label="ID" column="id"
                        default-sort="created_at" default-direction="desc" />
                    <x-admin.sortable-column label="Tên" column="name" />
                    <x-admin.sortable-column label="Đường dẫn" column="slug" />
                    <x-admin.sortable-column label="Hiển thị" column="is_active" />
                    <x-admin.sortable-column label="Ngày tạo" column="created_at"
                        default-sort="created_at" default-direction="desc" />
                    <x-admin.sortable-column label="Ngày cập nhật" column="updated_at"
                        default-sort="created_at" default-direction="desc" />
                    <th scope="col">
                        Thao tác
                    </th>
                </tr>
            </thead>
            <tbody>
                @forelse($records as $record)
                    <tr>
                        <td class="table-secondary table-id">#{{ $record->id }}</td>
                        <td>
                            <a class="table-title" href="{{ route('admin.'.$resource.'.edit', $record) }}">
                                {{ $record->name ?? $record->title }}
                            </a>
                        </td>
                        <td class="table-secondary">
                            {{ $record->slug }}
                        </td>
                        <td>
                            <x-admin.badge :tone="$record->is_active ? 'success' : 'neutral'">
                                {{ $record->is_active ? 'Đang bật' : 'Đang ẩn' }}
                            </x-admin.badge>
                        </td>
                        <x-admin.record-dates :record="$record" />
                        <td>
                            <div class="table-row-actions">
                                <a class="table-action" href="{{ route('admin.'.$resource.'.edit', $record) }}"
                                    aria-label="Sửa #{{ $record->id }}" title="Sửa">
                                    <x-admin.icon name="edit" />
                                </a>
                                <form
                                    method="post"
                                    action="{{ route('admin.'.$resource.'.destroy', $record) }}"
                                    data-confirm="Xóa nội dung này?"
                                >
                                    @csrf
                                    @method('DELETE')
                                    <button class="table-action danger" aria-label="Xóa #{{ $record->id }}" title="Xóa">
                                        <x-admin.icon name="trash" />
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">
                            <x-admin.empty-state title="Chưa có nội dung phù hợp"
                                description="Thử điều chỉnh bộ lọc hoặc thêm nội dung đầu tiên." />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </x-admin.table>
    </x-admin.table-block>
@endsection
