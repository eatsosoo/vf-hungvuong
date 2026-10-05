@extends('layouts.admin')
@section('title', 'Phân loại nội dung')
@section('content')
    <x-admin.page-heading :title="$kind === 'categories' ? 'Danh mục bài viết' : 'Thẻ nội dung'">
        <a class="button" href="{{ route('admin.taxonomies.index', [$kind, 'drawer' => 'new']) }}"
            data-drawer-open="taxonomy-drawer"
            data-drawer-title="{{ $kind === 'categories' ? 'Thêm danh mục bài viết' : 'Thêm thẻ nội dung' }}"
            data-drawer-action="{{ route('admin.taxonomies.store', $kind) }}"
            data-drawer-values="{{ json_encode(['_record' => '', 'name' => '', 'slug' => '']) }}"
            aria-haspopup="dialog" aria-controls="taxonomy-drawer">Thêm mới</a>
    </x-admin.page-heading>
    <form id="table-filters" class="table-toolbar" method="get"
        action="{{ route('admin.taxonomies.index', $kind) }}">
        <input type="hidden" name="per_page" value="{{ $records->perPage() }}">
        <div class="table-toolbar-copy">
            <strong>{{ number_format($records->total(), 0, ',', '.') }} phân loại</strong>
            <p>Lọc theo tên hoặc đường dẫn. Chọn biểu tượng sửa để mở biểu mẫu.</p>
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
                    <th scope="col" class="table-actions-column">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse($records as $record)
                    <tr>
                        <td class="table-secondary table-id">#{{ $record->id }}</td>
                        <td>
                            <span class="table-title">{{ $record->name }}</span>
                            @if($kind === 'categories')
                                <small class="block text-muted">{{ $record->posts_count }} bài viết</small>
                            @endif
                        </td>
                        <td>
                            {{ $record->slug }}
                        </td>
                        <x-admin.record-dates :record="$record" />
                        <td class="table-actions-column">
                            <div class="table-row-actions">
                                <a class="table-action"
                                    href="{{ route('admin.taxonomies.index', [$kind, 'drawer' => $record->id]) }}"
                                    data-drawer-open="taxonomy-drawer"
                                    data-drawer-title="{{ $kind === 'categories'
                                        ? 'Chỉnh sửa danh mục bài viết' : 'Chỉnh sửa thẻ nội dung' }}"
                                    data-drawer-action="{{ route('admin.taxonomies.update', [$kind, $record->id]) }}"
                                    data-drawer-values="{{ json_encode(['_record' => $record->id,
                                        'name' => $record->name, 'slug' => $record->slug]) }}"
                                    aria-label="Sửa #{{ $record->id }}" title="Sửa"
                                    aria-haspopup="dialog" aria-controls="taxonomy-drawer">
                                    <x-admin.icon name="edit" />
                                </a>
                                <form method="post"
                                    action="{{ route('admin.taxonomies.destroy', [$kind, $record->id]) }}"
                                    data-confirm="Xóa phân loại này?">
                                    @csrf
                                    @method('DELETE')
                                    <button class="table-action danger" aria-label="Xóa #{{ $record->id }}"
                                        @disabled($kind === 'categories' && $record->posts_count > 0)
                                        title="{{ $kind === 'categories' && $record->posts_count > 0
                                            ? 'Không thể xóa danh mục đang có bài viết' : 'Xóa' }}">
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
    <x-admin.form-drawer id="taxonomy-drawer" :auto-open="$openDrawer"
        :title="($taxonomy->exists ? 'Chỉnh sửa ' : 'Thêm ').($kind === 'categories'
            ? 'danh mục bài viết' : 'thẻ nội dung')">
        <form method="post"
            action="{{ $taxonomy->exists
                ? route('admin.taxonomies.update', [$kind, $taxonomy->id])
                : route('admin.taxonomies.store', $kind) }}">
            @csrf
            <input type="hidden" name="_method" value="PUT" @disabled(! $taxonomy->exists)>
            <input type="hidden" name="_drawer" value="taxonomy">
            <input type="hidden" name="_record" value="{{ $taxonomy->id }}">
            @include('admin.taxonomies.fields')
            <div class="admin-drawer-actions">
                <button type="button" class="secondary" data-drawer-cancel>Hủy</button>
                <button type="submit">Lưu phân loại</button>
            </div>
        </form>
    </x-admin.form-drawer>
@endsection
