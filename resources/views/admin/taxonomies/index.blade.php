@extends('layouts.admin')
@section('title', 'Phân loại nội dung')
@section('content')
    <x-admin.page-heading :title="$kind === 'categories' ? 'Danh mục bài viết' : 'Thẻ nội dung'" />
    <form class="panel two-columns" method="post" action="{{ route('admin.taxonomies.store', $kind) }}">
        @csrf
        <x-field name="name" label="Tên" required />
        <x-field name="slug" label="Đường dẫn" required />
        <button>
            Thêm mới
        </button>
    </form>
    <form id="table-filters" class="table-toolbar" method="get"
        action="{{ route('admin.taxonomies.index', $kind) }}">
        <input type="hidden" name="per_page" value="{{ $records->perPage() }}">
        <div class="table-toolbar-copy">
            <strong>{{ number_format($records->total(), 0, ',', '.') }} phân loại</strong>
            <p>Lọc theo tên hoặc đường dẫn. Chỉnh sửa trực tiếp rồi chọn Cập nhật.</p>
        </div>
        @if(request()->except('page', 'per_page', 'sort', 'direction'))
            <a class="button secondary" href="{{ route('admin.taxonomies.index', $kind) }}">Xóa bộ lọc</a>
        @endif
        @foreach(['sort', 'direction'] as $sortParameter)
            @if(request()->filled($sortParameter))
                <input type="hidden" name="{{ $sortParameter }}" value="{{ request($sortParameter) }}">
            @endif
        @endforeach
        <details class="table-filter-options">
            <summary>Tìm kiếm và bộ lọc</summary>
            <div class="table-filter-grid">
                <x-field name="name" id="taxonomy-filter-name" label="Tìm tên phân loại"
                    :value="request('name')" :use-old="false" form="table-filters" />

                <x-field name="slug" id="taxonomy-filter-slug" label="Tìm đường dẫn"
                    :value="request('slug')" :use-old="false" form="table-filters" />

            </div>
            <button type="submit" class="secondary">Áp dụng bộ lọc</button>
        </details>
    </form>
    <x-admin.table-block :records="$records">
        <x-admin.table :caption="$kind === 'categories' ? 'Danh mục bài viết' : 'Thẻ nội dung'">
            <thead>
                <tr>
                    <x-admin.sortable-column label="ID" column="id"
                        default-sort="name" default-direction="asc" />
                    <x-admin.sortable-column label="Tên" column="name" default-sort="name" />
                    <x-admin.sortable-column label="Đường dẫn" column="slug" default-sort="name" />
                    <x-admin.sortable-column label="Ngày tạo" column="created_at"
                        default-sort="name" default-direction="asc" />
                    <x-admin.sortable-column label="Ngày cập nhật" column="updated_at"
                        default-sort="name" default-direction="asc" />
                    <th scope="col">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse($records as $record)
                    <tr>
                        <td class="table-secondary table-id">#{{ $record->id }}</td>
                        <td>
                            <x-field name="name" :id="'taxonomy-name-'.$record->id" label="Tên phân loại"
                                :value="$record->name" :use-old="false"
                                :form="'taxonomy-update-'.$record->id" required />
                        </td>
                        <td>
                            <x-field name="slug" :id="'taxonomy-slug-'.$record->id" label="Đường dẫn"
                                :value="$record->slug" :use-old="false"
                                :form="'taxonomy-update-'.$record->id" required />
                        </td>
                        <x-admin.record-dates :record="$record" />
                        <td>
                            <div class="table-row-actions">
                                <form id="taxonomy-update-{{ $record->id }}"
                                    method="post" action="{{ route('admin.taxonomies.update', [$kind, $record->id]) }}">
                                    @csrf
                                    @method('PUT')
                                    <button class="table-action secondary" aria-label="Cập nhật #{{ $record->id }}"
                                        title="Cập nhật">
                                        <x-admin.icon name="save" />
                                    </button>
                                </form>
                                <form method="post"
                                    action="{{ route('admin.taxonomies.destroy', [$kind, $record->id]) }}"
                                    data-confirm="Xóa phân loại này?">
                                    @csrf
                                    @method('DELETE')
                                    <button class="table-action danger" aria-label="Xóa #{{ $record->id }}"
                                        title="Xóa">
                                        <x-admin.icon name="trash" />
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">
                            <x-admin.empty-state title="Chưa có phân loại phù hợp"
                                description="Thử thay đổi bộ lọc hoặc thêm danh mục, thẻ mới." />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </x-admin.table>
    </x-admin.table-block>
@endsection
