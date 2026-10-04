@extends('layouts.admin')
@section('title', 'Tổng quan')
@section('content')
    <x-admin.page-heading title="Tổng quan đại lý" eyebrow="Không gian quản trị"
        description="Theo dõi yêu cầu của khách hàng và công việc nội dung đang chờ xử lý." />
    <div class="mb-7 flex flex-col gap-6 rounded-2xl bg-night p-6 text-white sm:flex-row sm:items-center sm:p-8">
        <div class="min-w-0 flex-1">
            <p class="eyebrow text-brand!">VINFAST HÙNG VƯƠNG</p>
            <h2 class="mb-2! text-2xl!">Sẵn sàng cho hành trình mới.</h2>
            <p class="mb-0! text-sm text-white/75">Chào {{ auth()->user()->name }}, cùng chăm sóc từng trải nghiệm.</p>
        </div>
        <a href="{{ route('home') }}" class="button">Xem website<x-admin.icon name="external" /></a>
    </div>
    @php
        $stats = [
            ['label' => 'Khách mới', 'value' => $newLeads, 'icon' => 'users',
                'href' => route('admin.leads.index', ['status' => 'new'])],
            ['label' => 'Báo giá chờ xử lý', 'value' => $quotes, 'icon' => 'file',
                'href' => route('admin.leads.index', ['type' => 'quote'])],
            ['label' => 'Lịch lái thử', 'value' => $appointments, 'icon' => 'calendar',
                'href' => route('admin.leads.index', ['type' => 'test_drive'])],
            ['label' => 'Bài viết nháp', 'value' => $drafts, 'icon' => 'file',
                'href' => route('admin.posts.index', ['status' => 'draft'])],
        ];
    @endphp
    <div class="stat-grid">
        @foreach($stats as $stat)
            @if($stat['value'] !== null)
                <x-admin.stat-card :label="$stat['label']" :value="$stat['value']"
                    :icon="$stat['icon']" :href="$stat['href']" />
            @endif
        @endforeach
    </div>
    <section class="panel">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="mb-0!">Thông báo mới</h2>
            <x-admin.badge>{{ $notifications->count() }} chưa đọc</x-admin.badge>
        </div>
        @forelse($notifications as $notification)
            <div class="mt-4 flex items-center gap-3 rounded-xl border border-line p-4">
                <x-admin.icon name="bell" class="text-green-text" />
                <a href="{{ route('admin.leads.edit', $notification->data['lead_id']) }}">
                    Yêu cầu mới #{{ $notification->data['lead_id'] }}
                </a>
            </div>
        @empty
            <x-admin.empty-state title="Bạn đã xem hết thông báo" icon="check"
                description="Yêu cầu mới sẽ xuất hiện tại đây để bạn tiếp tục chăm sóc khách hàng." />
        @endforelse
        @if($notifications->isNotEmpty())
            <form class="form-actions" method="post" action="{{ route('admin.notifications.read') }}">
                @csrf
                <button class="secondary"><x-admin.icon name="check" />Đánh dấu đã đọc</button>
            </form>
        @endif
    </section>
@endsection
