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
        @include('admin.users.fields')
        <button>
            Lưu tài khoản
        </button>
    </form>
@endsection
