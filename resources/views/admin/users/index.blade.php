@extends('layouts.admin')
@section('title', 'Tài khoản & quyền')
@section('content')
    <x-admin.page-heading title="Tài khoản & quyền">
        <a class="button" href="{{ route('admin.users.create') }}">
            Thêm tài khoản
        </a>
    </x-admin.page-heading>
    <form id="table-filters" class="table-toolbar" method="get" action="{{ route('admin.users.index') }}">
        <input type="hidden" name="per_page" value="{{ $users->perPage() }}">
        <div class="table-toolbar-copy">
            <strong>{{ number_format($users->total(), 0, ',', '.') }} tài khoản</strong>
            <p>Lọc theo tên, email, vai trò hoặc tình trạng hoạt động.</p>
        </div>
        @if(request()->except('page', 'per_page', 'sort', 'direction'))
            <a class="button secondary" href="{{ route('admin.users.index') }}">Xóa bộ lọc</a>
        @endif
        @foreach(['sort', 'direction'] as $sortParameter)
            @if(request()->filled($sortParameter))
                <input type="hidden" name="{{ $sortParameter }}" value="{{ request($sortParameter) }}">
            @endif
        @endforeach
        <details class="table-filter-options">
            <summary>Tìm kiếm và bộ lọc</summary>
            <div class="table-filter-grid">
                <x-field name="name" label="Tên tài khoản" :value="request('name')"
                    :use-old="false" form="table-filters" />

                <x-field name="email" label="Tìm email" :value="request('email')"
                    :use-old="false" form="table-filters" />

                <x-select name="role" label="Vai trò tài khoản" form="table-filters">
                    <option value="">Tất cả vai trò</option>
                    @foreach(App\Enums\UserRole::cases() as $role)
                        <option value="{{ $role->value }}" @selected(request('role') === $role->value)>
                            {{ __('studio.role.'.$role->value) }}
                        </option>
                    @endforeach
                </x-select>

                <x-select name="is_active" label="Tình trạng hoạt động" form="table-filters">
                    <option value="">Tất cả</option>
                    <option value="1" @selected(request('is_active') === '1')>Đang hoạt động</option>
                    <option value="0" @selected(request('is_active') === '0')>Đã khóa</option>
                </x-select>

            </div>
            <button type="submit" class="secondary">Áp dụng bộ lọc</button>
        </details>
    </form>
    <x-admin.table-block :records="$users">
        <x-admin.table caption="Tài khoản và quyền">
            <thead>
                <tr>
                    <x-admin.sortable-column label="ID" column="id"
                        default-sort="name" default-direction="asc" />
                    <x-admin.sortable-column label="Tên" column="name" default-sort="name" />
                    <x-admin.sortable-column label="Email" column="email" default-sort="name" />
                    <x-admin.sortable-column label="Vai trò" column="role" default-sort="name" />
                    <x-admin.sortable-column label="Hoạt động" column="is_active" default-sort="name" />
                    <x-admin.sortable-column label="Ngày tạo" column="created_at"
                        default-sort="name" default-direction="asc" />
                    <x-admin.sortable-column label="Ngày cập nhật" column="updated_at"
                        default-sort="name" default-direction="asc" />
                    <th scope="col" class="table-actions-column">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                    <tr>
                        <td class="table-secondary table-id">#{{ $user->id }}</td>
                        <td>
                            <span class="table-title">{{ $user->name }}</span>
                        </td>
                        <td>
                            {{ $user->email }}
                        </td>
                        <td>
                            {{ __('studio.role.'.$user->role->value) }}
                        </td>
                        <td>
                            <x-admin.badge :tone="$user->is_active ? 'success' : 'danger'">
                                {{ $user->is_active ? 'Đang hoạt động' : 'Đã khóa' }}
                            </x-admin.badge>
                        </td>
                        <x-admin.record-dates :record="$user" />
                        <td class="table-actions-column">
                            <div class="table-row-actions">
                                <a class="table-action" href="{{ route('admin.users.edit', $user) }}"
                                    aria-label="Sửa #{{ $user->id }}" title="Sửa">
                                    <x-admin.icon name="edit" />
                                </a>

                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8">
                            <x-admin.empty-state title="Chưa có tài khoản phù hợp"
                                description="Thử thay đổi bộ lọc hoặc thêm tài khoản mới." />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </x-admin.table>
    </x-admin.table-block>
@endsection
