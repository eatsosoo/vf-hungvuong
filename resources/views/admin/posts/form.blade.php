@extends('layouts.admin')
@section('title', 'Soạn bài viết')
@push('assets')
    @vite(['resources/css/editor.css', 'resources/js/editor.js'])
@endpush
@section('content')
    @php
        $scheduledAt = $post->status === App\Enums\PostStatus::Scheduled
            ? $post->published_at?->format('Y-m-d\TH:i') : null;
    @endphp
    <x-admin.page-heading :title="$post->exists ? 'Chỉnh sửa bài viết' : 'Viết bài mới'">
        @if($post->exists)
            <a href="{{ route('admin.posts.preview', $post) }}">
                Xem trước bản đã lưu
            </a>
        @endif
    </x-admin.page-heading>
    <form
        class="editor-grid"
        method="post"
        action="{{ $post->exists ? route('admin.posts.update', $post) : route('admin.posts.store') }}"
        data-post-form
    >
        @csrf
        @if($post->exists)
            @method('PUT')
        @endif
        <section class="panel">
            <x-field name="title" label="Tiêu đề bài viết" :value="$post->title" required />
            <x-field name="slug" label="Đường dẫn (vd: danh-gia-vf7)" :value="$post->slug"
                    slug-source="title" :slug-auto="! $post->exists" required />
            <x-field name="excerpt" label="Tóm tắt" type="textarea" :value="$post->excerpt" />
            <x-admin.post-editor :post="$post" :media="$media" />
            <details>
                <summary>
                    Hướng dẫn chèn nội dung
                </summary>
                <p>
                    Ảnh: lấy URL từ thư viện.
                    Video: dùng [video](https://www.youtube.com/watch?v=ID_VIDEO) trên dòng riêng.
                    HTML tùy ý bị loại bỏ để bảo vệ người đọc.
                </p>
            </details>
            <h2>
                Thông tin SEO
            </h2>
            <x-field name="focus_keyword" label="Cụm từ chính" :value="$post->focus_keyword"
                hint="Chủ đề người đọc tìm kiếm. Dùng tự nhiên trong tiêu đề và nội dung." maxlength="120" />
            <x-field name="seo_title" label="Tiêu đề SEO" :value="$post->seo_title" />
            <x-field name="seo_description" label="Mô tả SEO" type="textarea" :value="$post->seo_description" />
            <x-field
                name="canonical_url"
                label="Canonical (tùy chọn, cùng tên miền)"
                type="url"
                :value="$post->canonical_url" />
            <x-admin.media-picker :name="'share_media_id'" :selected="$post->share_media_id"
                label="Ảnh chia sẻ" :media="$media" />
        </section>
        <aside class="panel">
            <h2>
                Xuất bản
            </h2>
            <x-select name="status" label="Trạng thái">
                <option value="draft">
                    Nháp
                </option>
                @can('publish', App\Models\Post::class)
                    <option value="scheduled" @selected(old('status', $post->status?->value) === 'scheduled')>
                        Hẹn giờ
                    </option>
                    <option value="published" @selected(old('status', $post->status?->value) === 'published')>
                        Xuất bản
                    </option>
                @endcan
            </x-select>
            <x-field
                name="published_at"
                label="Thời gian hẹn đăng (giờ Việt Nam)"
                type="datetime-local"
                :value="$scheduledAt" />
            <x-select name="category_id" label="Danh mục">
                <option value="">
                    Không chọn
                </option>
                @foreach($categories as $category)
                    <option
                        value="{{ $category->id }}"
                        @selected(old('category_id', $post->category_id) == $category->id)
                    >
                        {{ $category->name }}
                    </option>
                @endforeach
            </x-select>
            <x-admin.media-picker :name="'media_id'" :selected="$post->media_id"
                label="Ảnh đại diện" :media="$media" />
            <x-select name="tags[]" label="Thẻ"
                hint="Giữ Ctrl (Windows) hoặc Command (Mac) để chọn nhiều mục." multiple size="6">
                @foreach($tags as $tag)
                    <option
                        value="{{ $tag->id }}"
                        @selected(in_array($tag->id, old('tags', $post->tags->modelKeys())))
                    >
                        {{ $tag->name }}
                    </option>
                @endforeach
            </x-select>
            <x-select name="vehicles[]" label="Xe liên quan"
                hint="Giữ Ctrl (Windows) hoặc Command (Mac) để chọn nhiều mục." multiple size="6">
                @foreach($vehicles as $vehicle)
                    <option
                        value="{{ $vehicle->id }}"
                        @selected(in_array($vehicle->id, old('vehicles', $post->vehicles->modelKeys())))
                    >
                        {{ $vehicle->name }}
                    </option>
                @endforeach
            </x-select>
            <button>
                Lưu bài viết
            </button>
            <div class="mt-8 border-t border-line pt-7">
                <x-admin.seo-assessment :assessment="$assessment" />
            </div>
        </aside>
    </form>
    @stack('editor-dialogs')
    @if($post->exists)
        @can('delete', $post)
            <form
                method="post"
                action="{{ route('admin.posts.destroy', $post) }}"
                data-confirm="Xóa bài viết và chuyển hướng liên quan?"
            >
                @csrf
                @method('DELETE')
                <button class="danger">
                    Xóa bài viết
                </button>
            </form>
        @endcan
    @endif
@endsection
