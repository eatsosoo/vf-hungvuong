@extends('layouts.admin')
@section('title', 'Đổi mật khẩu')
@section('content')
    <x-admin.page-heading title="Đổi mật khẩu" />
    <form class="panel narrow" method="post" action="{{ route('admin.password.update') }}">
        @csrf
        @method('PUT')
        <x-field name="current_password" label="Mật khẩu hiện tại" type="password"
            required autocomplete="current-password" />
        <p>
            Ít nhất 12 ký tự, gồm chữ hoa, chữ thường, số và ký hiệu.
        </p>
        <x-field name="password" label="Mật khẩu mới" type="password" required />
        <x-field name="password_confirmation" label="Nhập lại mật khẩu" type="password" required />
        <button>
            Lưu mật khẩu và đăng nhập lại
        </button>
    </form>
@endsection
