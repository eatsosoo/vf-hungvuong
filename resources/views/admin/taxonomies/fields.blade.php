<x-field name="name" id="taxonomy-drawer-name" label="Tên phân loại"
    :value="$taxonomy->name" required maxlength="100" />
<x-field name="slug" id="taxonomy-drawer-slug" label="Đường dẫn" slug-source="name"
    :slug-auto="! $taxonomy->exists" :value="$taxonomy->slug" maxlength="180" />
