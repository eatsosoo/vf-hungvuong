@extends('layouts.admin')
@section('title', 'Quản lý tài khoản')
@section('content')
    <x-admin.page-heading :title="$account->exists ? 'Chỉnh sửa tài khoản' : 'Thêm tài khoản'" />
    <form
        class="panel narrow"
        method="post"
        action="{{ $account->exists ? route('admin.users.update', $account) : route('admin.users.store') }}"
    >
        @csrf
        @if($account->exists)
            @method('PUT')
        @endif
        <x-field name="name" label="Họ tên" :value="$account->name" required />
        <x-field name="email" label="Email" type="email" :value="$account->email" required />
        <x-select name="role" label="Vai trò">
            @foreach(App\Enums\UserRole::cases() as $role)
                <option value="{{ $role->value }}" @selected(old('role', $account->role?->value) === $role->value)>
                    {{ __('studio.role.'.$role->value) }}
                </option>
            @endforeach
        </x-select>
        <x-select name="is_active" label="Hoạt động">
            <option value="1">
                Bật
            </option>
            <option value="0" @selected((string) old('is_active', $account->is_active ?? true) === '0')>
                Khóa
            </option>
        </x-select>
        <p>
            Mật khẩu ít nhất 12 ký tự gồm chữ hoa/thường, số và ký hiệu. Khi sửa, để trống để giữ mật khẩu.
        </p>
        <x-field name="password" label="Mật khẩu" type="password" :required="! $account->exists" />
        <x-field
            name="password_confirmation"
            label="Nhập lại mật khẩu"
            type="password"
            :required="! $account->exists" />
        <button>
            Lưu tài khoản
        </button>
    </form>
@endsection
