@props(['index', 'row' => [], 'media'])
<article class="vehicle-color-card" data-vehicle-color-row>
    <div class="vehicle-color-card-heading">
        <div class="flex min-w-0 items-center gap-3">
            <svg class="vehicle-admin-swatch" viewBox="0 0 32 32" aria-hidden="true">
                <rect width="32" height="32" rx="16" fill="#ffffff" data-color-preview-body />
                <path d="M0 16a16 16 0 0 1 32 0Z" fill="#ffffff" data-color-preview-roof />
            </svg>
            <strong data-color-row-title>Màu xe</strong>
        </div>
        <button type="button" class="vehicle-card-delete danger" data-remove-vehicle-color
            aria-label="Xóa card màu xe khỏi biểu mẫu" title="Xóa card màu">
            <x-admin.icon name="trash" />
        </button>
    </div>
    <x-field :name="'colors['.$index.'][name]'" label="Tên màu" :value="$row['name'] ?? null" />
    <div class="two-columns">
        <x-admin.color-picker :name="'colors['.$index.'][hex]'" label="Màu thân xe"
            :value="$row['hex'] ?? null" />
        <x-admin.color-picker :name="'colors['.$index.'][secondary_hex]'" label="Màu nóc (tùy chọn)"
            :value="$row['secondary_hex'] ?? null" />
    </div>
    <x-admin.media-picker :name="'colors['.$index.'][media_id]'" :selected="$row['media_id'] ?? null"
        label="Ảnh xe theo màu" :media="$media" />
</article>
