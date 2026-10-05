@extends('layouts.site')
@section('title', __('Dòng xe VinFast · Hùng Vương'))
@section('content')
    <p class="eyebrow">
        {{ __('HÀNH TRÌNH THUẦN ĐIỆN') }}
    </p>
    <div class="flex flex-wrap items-end justify-between gap-5">
        <h1>{{ __('Chọn chiếc xe phù hợp với bạn.') }}</h1>
        <a class="client-button-secondary" href="{{ route($clientRoutePrefix.'vehicles.compare') }}">
            {{ __('So sánh xe') }} <x-admin.icon name="arrow" class="size-4" />
        </a>
    </div>
    <form class="filters" method="get">
        <x-field name="q" label="{{ __('Tìm mẫu xe') }}" :value="request('q')" />
        <label class="field">
            {{ __('Phân khúc') }}
            <select name="segment">
                <option value="">
                    {{ __('Tất cả') }}
                </option>
                @foreach($segments as $segment)
                    <option @selected(request('segment') === $segment)>
                        {{ $segment }}
                    </option>
                @endforeach
            </select>
        </label>
        <button>
            {{ __('Tìm xe') }}
        </button>
    </form>
    <div class="card-grid">
        @forelse($vehicles as $vehicle)
            <x-vehicle-card :vehicle="$vehicle" />
        @empty
            <p>
                {{ __('Đang cập nhật danh mục xe.') }}
            </p>
        @endforelse
    </div>
    {{ $vehicles->links() }}
@endsection
