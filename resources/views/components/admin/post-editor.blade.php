@props([
    'post' => null, 'record' => null, 'media', 'scope' => 'post',
    'label' => 'Nội dung bài viết', 'placeholder' => 'Bắt đầu bài viết bằng vấn đề người đọc đang quan tâm…',
])
@php
    $contentRecord = $record ?? $post;
    $tools = [
        ['bold', 'In đậm (Ctrl/Cmd+B)', 'bold'],
        ['italic', 'In nghiêng (Ctrl/Cmd+I)', 'italic'],
        ['strike', 'Gạch ngang', 'strike'],
        ['bulletList', 'Danh sách dấu đầu dòng', 'list'],
        ['orderedList', 'Danh sách đánh số', 'ordered-list'],
        ['blockquote', 'Trích dẫn', 'quote'],
        ['codeBlock', 'Khối mã', 'code'],
        ['horizontalRule', 'Đường phân cách', 'minus'],
        ['link', 'Chèn hoặc sửa liên kết', 'link'],
        ['unlink', 'Bỏ liên kết', 'unlink'],
        ['image', 'Chèn ảnh', 'image'],
        ['video', 'Chèn video YouTube', 'video'],
        ['table', 'Chèn bảng', 'table'],
        ['undo', 'Hoàn tác (Ctrl/Cmd+Z)', 'undo'],
        ['redo', 'Làm lại (Ctrl/Cmd+Shift+Z)', 'redo'],
    ];
@endphp
<div class="post-editor" data-post-editor data-analysis-url="{{ route('admin.posts.analyze') }}"
    data-placeholder="{{ $placeholder }}"
    data-pending-key="vf-{{ $scope }}-pending-{{ auth()->id() }}"
    data-draft-prefix="vf-{{ $scope }}-draft-{{ auth()->id() }}-"
    data-save-succeeded="{{ session()->has('success') ? 'true' : 'false' }}"
    data-draft-key="vf-{{ $scope }}-draft-{{ auth()->id() }}-{{ $contentRecord->id ?? 'new' }}">
    <div class="editor-recovery" data-draft-recovery hidden>
        <p>Có nội dung chưa lưu trong phiên làm việc này.</p>
        <div class="actions">
            <button type="button" class="secondary" data-restore-draft>Khôi phục nội dung</button>
            <button type="button" class="secondary" data-discard-draft>Bỏ bản khôi phục</button>
        </div>
    </div>
    <div class="editor-heading">
        <div>
            <label class="field-label" id="editor-body-label" for="field-body">{{ $label }}</label>
            <p class="field-hint mt-1" id="editor-body-hint">Soạn trực quan hoặc chuyển sang Markdown khi cần.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <button type="button" class="editor-mode" data-editor-source aria-pressed="false">Markdown</button>
            <button type="button" class="editor-mode" data-editor-preview aria-pressed="false">
                <x-admin.icon name="eye" class="size-4" />Xem trước
            </button>
            <button type="button" class="editor-mode" data-editor-fullscreen aria-pressed="false"
                aria-label="Mở editor toàn màn hình" title="Toàn màn hình">
                <x-admin.icon name="expand" />
            </button>
        </div>
    </div>
    <div class="editor-controls" data-editor-controls>
        <label class="mb-0 flex items-center gap-3 text-sm font-semibold" for="editor-heading-level">
            Định dạng
            <select id="editor-heading-level" data-heading-level class="w-auto! min-w-36">
                <option value="paragraph">Đoạn văn</option>
                <option value="2">Tiêu đề H2</option>
                <option value="3">Tiêu đề H3</option>
                <option value="4">Tiêu đề H4</option>
            </select>
        </label>
        <div class="editor-toolbar" role="toolbar" aria-label="Định dạng nội dung">
            @foreach($tools as [$command, $label, $icon])
                <button type="button" class="editor-tool" data-editor-command="{{ $command }}"
                    aria-label="{{ $label }}" title="{{ $label }}" tabindex="{{ $loop->first ? 0 : -1 }}">
                    @if($icon === 'minus')
                        <span aria-hidden="true" class="text-xl">−</span>
                    @else
                        <x-admin.icon :name="$icon" />
                    @endif
                </button>
            @endforeach
        </div>
        <details class="editor-table-tools" data-table-tools hidden>
            <summary>Chỉnh sửa bảng đang chọn</summary>
            <div class="flex flex-wrap gap-2">
                @foreach([
                    'addRowAfter' => 'Thêm hàng', 'deleteRow' => 'Xóa hàng',
                    'addColumnAfter' => 'Thêm cột', 'deleteColumn' => 'Xóa cột',
                    'toggleHeaderRow' => 'Hàng tiêu đề', 'deleteTable' => 'Xóa bảng',
                ] as $command => $label)
                    <button type="button" class="editor-mode" data-table-command="{{ $command }}">{{ $label }}</button>
                @endforeach
            </div>
        </details>
    </div>
    <div class="editor-canvas" data-editor-canvas></div>
    <div data-editor-markdown>
        <x-field name="body" label="Nội dung Markdown" type="textarea" :value="$contentRecord->body"
            :rows="18" required hint="Nội dung được lưu khi bạn nhấn nút Lưu." />
    </div>
    <div class="editor-preview prose" data-editor-rendered hidden aria-label="Xem trước nội dung"></div>
    <div class="editor-footer">
        <span data-editor-count>Đang chuẩn bị editor…</span>
        <span data-editor-draft-status role="status"></span>
    </div>
    <p class="error px-4 pb-4" data-editor-error role="alert" hidden></p>
</div>
@push('editor-dialogs')
    <dialog class="editor-dialog" data-editor-dialog="link" aria-labelledby="editor-link-title">
        <div class="editor-dialog-heading">
            <h2 id="editor-link-title">Chèn liên kết</h2>
            <button type="button" class="editor-mode" data-close-dialog aria-label="Đóng hộp thoại">
                <x-admin.icon name="close" />
            </button>
        </div>
        <x-field name="editor_link_url" label="URL liên kết" type="url" placeholder="https://…" />
        <p class="field-hint">Có thể dùng đường dẫn nội bộ bắt đầu bằng /, hoặc URL https/http.</p>
        <div class="form-actions"><button type="button" data-insert-link>Chèn liên kết</button></div>
        <p class="error mt-3" data-dialog-error role="alert" hidden></p>
    </dialog>
    <dialog class="editor-dialog" data-editor-dialog="video" aria-labelledby="editor-video-title">
        <div class="editor-dialog-heading">
            <h2 id="editor-video-title">Chèn video YouTube</h2>
            <button type="button" class="editor-mode" data-close-dialog aria-label="Đóng hộp thoại">
                <x-admin.icon name="close" />
            </button>
        </div>
        <x-field name="editor_video_url" label="URL video" type="url"
            placeholder="https://www.youtube.com/watch?v=…" />
        <p class="field-hint">Video chỉ tải trên trang công khai khi người đọc nhấn phát.</p>
        <div class="form-actions"><button type="button" data-insert-video>Chèn video</button></div>
        <p class="error mt-3" data-dialog-error role="alert" hidden></p>
    </dialog>
    <dialog class="editor-dialog editor-media-dialog" data-editor-dialog="image" aria-labelledby="editor-image-title">
        <div class="editor-dialog-heading">
            <h2 id="editor-image-title">Chèn ảnh vào nội dung</h2>
            <button type="button" class="editor-mode" data-close-dialog aria-label="Đóng hộp thoại">
                <x-admin.icon name="close" />
            </button>
        </div>
        <x-field name="editor_image_url" label="URL ảnh" type="url" placeholder="https://…" />
        <x-field name="editor_image_alt" label="Mô tả ảnh (alt)"
            hint="Mô tả điều người đọc nhìn thấy; tránh nhồi từ khóa." />
        <div class="rounded-xl border border-line bg-paper p-4">
            <x-field name="editor_image_file" label="Tải ảnh mới" type="file" accept="image/png,image/jpeg,image/webp"
                hint="JPG, PNG hoặc WebP; tối đa 5 MB. Ảnh được tối ưu trước khi lưu." />
            <button type="button" class="secondary" data-upload-editor-image
                data-upload-url="{{ route('admin.media.store') }}">Tải lên thư viện</button>
        </div>
        @if($media->isNotEmpty())
            <details class="mt-5" open>
                <summary>Chọn từ thư viện ảnh</summary>
                <div class="editor-media-grid">
                    @foreach($media as $item)
                        <button type="button" class="editor-media-item" data-library-image
                            data-url="{{ $item->url() }}" data-alt="{{ $item->alt ?: $item->original_name }}"
                            aria-label="Chọn ảnh {{ $item->alt ?: $item->original_name }}">
                            <img src="{{ $item->url() }}" alt="" width="150" height="100" loading="lazy">
                            <span>{{ $item->alt ?: $item->original_name }}</span>
                        </button>
                    @endforeach
                </div>
            </details>
        @endif
        <div class="form-actions"><button type="button" data-insert-image>Chèn ảnh</button></div>
        <p class="error mt-3" data-dialog-error role="alert" hidden></p>
    </dialog>
@endpush
