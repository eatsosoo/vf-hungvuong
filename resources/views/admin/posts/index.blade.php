@extends('layouts.admin')
@section('title', 'Bài viết SEO')
@section('content')
    <x-admin.page-heading title="Bài viết SEO">
        <a class="button" href="{{ route('admin.posts.create') }}">
            Viết bài mới
        </a>
    </x-admin.page-heading>
    <form id="table-filters" class="table-toolbar" method="get" action="{{ route('admin.posts.index') }}">
        <input type="hidden" name="per_page" value="{{ $posts->perPage() }}">
        <div class="table-toolbar-copy">
            <strong>{{ number_format($posts->total(), 0, ',', '.') }} bài viết</strong>
            <p>Nhấn tiêu đề cột để sắp xếp tăng hoặc giảm dần.</p>
        </div>
        @if(request()->except('page', 'per_page', 'sort', 'direction'))
            <a class="button secondary" href="{{ route('admin.posts.index') }}">Xóa bộ lọc</a>
        @endif
        @foreach(['sort', 'direction'] as $sortParameter)
            @if(request()->filled($sortParameter))
                <input type="hidden" name="{{ $sortParameter }}" value="{{ request($sortParameter) }}">
            @endif
        @endforeach
        <details class="table-filter-options">
            <summary>Tìm kiếm và bộ lọc</summary>
            <div class="table-filter-grid">
                <x-field name="q" label="Tìm tiêu đề" :value="request('q')"
                    :use-old="false" form="table-filters" />
                <x-select name="category_id" label="Danh mục" form="table-filters">
                    <option value="">Tất cả danh mục</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" @selected(request('category_id') == $category->id)>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </x-select>

                <x-select name="status" label="Trạng thái xuất bản" form="table-filters">
                    <option value="">Tất cả trạng thái</option>
                    @foreach(['draft' => 'Nháp', 'scheduled' => 'Hẹn giờ',
                        'published' => 'Đã xuất bản'] as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>
                            {{ $label }}
                        </option>
                    @endforeach
                </x-select>
                <x-field name="published_from" label="Ngày xuất bản từ" type="date"
                    :value="request('published_from')" :use-old="false" form="table-filters" />
                <x-field name="published_to" label="Ngày xuất bản đến" type="date"
                    :value="request('published_to')" :use-old="false" form="table-filters" />

                <x-select name="user_id" label="Chọn tác giả" form="table-filters">
                    <option value="">Tất cả tác giả</option>
                    @foreach($authors as $author)
                        <option value="{{ $author->id }}" @selected(request('user_id') == $author->id)>
                            {{ $author->name }}
                        </option>
                    @endforeach
                </x-select>

                <x-select name="seo" label="Thông tin SEO" form="table-filters">
                    <option value="">Tất cả</option>
                    <option value="complete" @selected(request('seo') === 'complete')>Đã điền</option>
                    <option value="incomplete" @selected(request('seo') === 'incomplete')>
                        Cần bổ sung
                    </option>
                </x-select>

            </div>
            <button type="submit" class="secondary">Áp dụng bộ lọc</button>
        </details>
    </form>
    <x-admin.table-block :records="$posts">
        <form
            method="post"
            action="{{ route('admin.posts.bulk') }}"
            data-confirm="Áp dụng cho các bài đã chọn?"
        >
            @csrf
            <x-admin.table caption="Bài viết SEO">
                <thead>
                    <tr>
                        <x-admin.sortable-column label="ID" column="id"
                            default-sort="created_at" default-direction="desc" />
                        <th scope="col">
                            Chọn
                        </th>
                        <x-admin.sortable-column label="Tiêu đề" column="title" />
                        <x-admin.sortable-column label="Trạng thái" column="status" />
                        <x-admin.sortable-column label="Tác giả" column="author" />
                        <x-admin.sortable-column label="SEO" column="seo" />
                        <x-admin.sortable-column label="Ngày tạo" column="created_at"
                            default-sort="created_at" default-direction="desc" />
                        <x-admin.sortable-column label="Ngày cập nhật" column="updated_at"
                            default-sort="created_at" default-direction="desc" />
                        <th scope="col" class="table-actions-column">
                            Thao tác
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($posts as $post)
                        <tr>
                            <td class="table-secondary table-id">#{{ $post->id }}</td>
                            <td>
                                <label class="inline-flex min-h-11 min-w-11 cursor-pointer items-center justify-center">
                                    <input
                                        type="checkbox"
                                        name="ids[]"
                                        value="{{ $post->id }}"
                                        aria-label="Chọn {{ $post->title }}"
                                    >
                                </label>
                            </td>
                            <td>
                                <span class="table-title">{{ $post->title }}</span>
                                <small>
                                    {{ $post->category?->name }}
                                </small>
                            </td>
                            <td>
                                <x-admin.badge :tone="match ($post->status->value) {
                                    'published' => 'success', 'scheduled' => 'info', default => 'neutral',
                                    }">
                                    {{ ['draft' => 'Nháp',
                                    'scheduled' => 'Hẹn giờ',
                                    'published' => 'Đã xuất bản'][$post->status->value] }}
                                </x-admin.badge>
                                <small>
                                    {{ $post->published_at?->format('d/m/Y H:i') }}
                                </small>
                            </td>
                            <td>
                                {{ $post->author->name }}
                            </td>
                            <td>
                                <x-admin.badge :tone="filled($post->seo_title) && filled($post->seo_description)
                                    && $post->media_id
                                    ? 'success' : 'warning'">
                                    {{ filled($post->seo_title) && filled($post->seo_description) && $post->media_id
                                    ? 'Đã điền' : 'Cần bổ sung' }}
                                </x-admin.badge>
                            </td>
                            <x-admin.record-dates :record="$post" />
                            <td class="table-actions-column">
                                <div class="table-row-actions">
                                    @can('update', $post)
                                        <a class="table-action" href="{{ route('admin.posts.edit', $post) }}"
                                            aria-label="Sửa #{{ $post->id }}" title="Sửa">
                                            <x-admin.icon name="edit" />
                                        </a>
                                    @endcan
                                    <a class="table-action" href="{{ route('admin.posts.preview', $post) }}"
                                        aria-label="Xem trước #{{ $post->id }}" title="Xem trước">
                                        <x-admin.icon name="eye" />
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9">
                                <x-admin.empty-state title="Chưa có bài viết phù hợp"
                                    description="Thử thay đổi bộ lọc hoặc bắt đầu viết bài mới." />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </x-admin.table>
            @can('publish', App\Models\Post::class)
                <div class="filters">
                    <x-select name="operation" label="Thao tác hàng loạt">
                        <option value="draft">
                            Chuyển về nháp
                        </option>
                        <option value="delete">
                            Xóa
                        </option>
                    </x-select>
                    <button>
                        Áp dụng bài được chọn
                    </button>
                </div>
            @endcan
        </form>
    </x-admin.table-block>
@endsection
