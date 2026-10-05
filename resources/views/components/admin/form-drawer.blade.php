@props(['id', 'title', 'autoOpen' => false])
<dialog id="{{ $id }}" class="admin-form-drawer" data-admin-drawer
    data-auto-open="{{ $autoOpen ? 'true' : 'false' }}" aria-labelledby="{{ $id }}-title">
    <header class="admin-drawer-header">
        <div>
            <p class="text-xs font-medium text-muted">QUẢN TRỊ ĐẠI LÝ</p>
            <h2 id="{{ $id }}-title" data-drawer-title>{{ $title }}</h2>
        </div>
        <button type="button" class="table-action secondary" data-drawer-close aria-label="Đóng biểu mẫu">
            <x-admin.icon name="close" />
        </button>
    </header>
    <div class="admin-drawer-body">
        @if($autoOpen && $errors->any())
            <div class="error-box" role="alert" data-drawer-errors>
                <strong>Vui lòng kiểm tra lại thông tin:</strong>
                <ul>
                    @foreach($errors->all() as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        {{ $slot }}
    </div>
</dialog>
<noscript>
    <style>
        #{{ $id }} {
            display: block;
            position: static;
            width: 100%;
            height: auto;
            margin-top: 24px;
        }
        #{{ $id }} [data-drawer-close] { display: none; }
    </style>
</noscript>
