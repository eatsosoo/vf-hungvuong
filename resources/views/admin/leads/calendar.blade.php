@extends('layouts.admin')
@section('title', 'Lịch lái thử')
@section('content')
    @php
        $month = $calendar['month']->format('Y-m');
        $currentFilters = array_merge($calendarFilters, ['month' => $month]);
        $weekdays = ['Thứ Hai', 'Thứ Ba', 'Thứ Tư', 'Thứ Năm', 'Thứ Sáu', 'Thứ Bảy', 'Chủ Nhật'];
    @endphp
    <x-admin.page-heading title="Lịch lái thử"
        description="Theo dõi lịch hẹn đã xếp và thời gian khách đề xuất. Chọn một lịch để xử lý yêu cầu.">
        <nav class="actions" aria-label="Chế độ hiển thị lịch lái thử">
            <a class="button" aria-current="page" href="{{ route('admin.leads.index', $currentFilters) }}">
                <x-admin.icon name="calendar" /> Lịch tháng
            </a>
            <a class="button secondary"
                href="{{ route('admin.leads.index', array_merge($currentFilters, ['view' => 'list'])) }}">
                <x-admin.icon name="menu" /> Danh sách
            </a>
        </nav>
    </x-admin.page-heading>
    <div class="grid gap-4 sm:grid-cols-3">
        <x-admin.stat-card label="Lịch trong tháng" :value="$calendar['total']" icon="calendar" />
        <x-admin.stat-card label="Đã xác nhận" :value="$calendar['confirmed']" icon="check" />
        <x-admin.stat-card label="Đang chờ xử lý" :value="$calendar['pending']" icon="inbox" />
    </div>
    <form class="filters" method="get" action="{{ route('admin.leads.index') }}">
        <input type="hidden" name="type" value="test_drive">
        <input type="hidden" name="view" value="calendar">
        <x-field name="month" label="Tháng hiển thị" type="month" :value="$month" min="1900-01" max="2099-12" />
        <x-field name="q" label="Tìm tên khách" :value="request('q')" />
        <x-select name="status" label="Trạng thái">
            <option value="">Tất cả trạng thái</option>
            @foreach(App\Enums\LeadStatus::forType('test_drive') as $status)
                <option value="{{ $status->value }}" @selected(request('status') === $status->value)>
                    {{ __('studio.status.'.$status->value) }}
                </option>
            @endforeach
        </x-select>
        <button type="submit">Áp dụng</button>
        @if(request()->filled('q') || request()->filled('status'))
            <a class="button secondary"
                href="{{ route('admin.leads.index', [
                    'type' => 'test_drive', 'view' => 'calendar', 'month' => $month,
                ]) }}">
                Xóa bộ lọc
            </a>
        @endif
    </form>
    <section class="panel p-0! overflow-hidden" aria-labelledby="calendar-heading">
        <div class="flex flex-wrap items-center justify-between gap-4 border-b border-line p-5 sm:p-6">
            <div>
                <h2 id="calendar-heading" class="mb-1!">{{ $calendar['month']->format('m/Y') }}</h2>
                <p class="mb-0! text-sm text-muted">Giờ Việt Nam · Tuần bắt đầu từ thứ Hai</p>
            </div>
            <nav class="actions" aria-label="Điều hướng tháng">
                @if($calendar['previousMonth'])
                    <a class="button secondary" rel="prev" aria-label="Tháng trước"
                        href="{{ route('admin.leads.index', array_merge($calendarFilters,
                            ['month' => $calendar['previousMonth']])) }}">
                        <x-admin.icon name="arrow" class="rotate-180" />
                        <span class="hidden sm:inline">Trước</span>
                    </a>
                @endif
                <a class="button secondary"
                    href="{{ route('admin.leads.index', array_merge($calendarFilters,
                        ['month' => $calendar['todayMonth']])) }}">Hôm nay</a>
                @if($calendar['nextMonth'])
                    <a class="button secondary" rel="next" aria-label="Tháng sau"
                        href="{{ route('admin.leads.index', array_merge($calendarFilters,
                            ['month' => $calendar['nextMonth']])) }}">
                        <span class="hidden sm:inline">Sau</span>
                        <x-admin.icon name="arrow" />
                    </a>
                @endif
            </nav>
        </div>
        <div class="hidden lg:block">
            <table class="w-full table-fixed border-collapse text-left">
                <caption class="sr-only">Lịch lái thử tháng {{ $calendar['month']->format('m/Y') }}</caption>
                <thead>
                    <tr>
                        @foreach($weekdays as $weekday)
                            <th scope="col" class="border-b border-line bg-paper p-3 text-sm font-semibold text-muted">
                                {{ $weekday }}
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($calendar['weeks'] as $week)
                        <tr>
                            @foreach($week as $day)
                                <td @class([
                                    'border-r border-b border-line p-2 align-top last:border-r-0',
                                    'bg-white' => $day['isCurrentMonth'],
                                    'bg-paper text-muted' => ! $day['isCurrentMonth'],
                                ])>
                                    <div class="min-h-36">
                                        <div class="mb-2 flex flex-wrap items-center gap-1.5">
                                            <time datetime="{{ $day['date']->format('Y-m-d') }}"
                                                aria-label="{{ $day['date']->format('d/m/Y') }}"
                                                @class([
                                                    'inline-flex size-8 items-center justify-center',
                                                    'rounded-full text-sm',
                                                    'bg-brand font-bold text-night' => $day['isToday'],
                                                    'font-semibold' => $day['isCurrentMonth'],
                                                ])>{{ $day['date']->format('j') }}</time>
                                            @if($day['isToday'])
                                                <span class="text-xs font-semibold text-green-text">Hôm nay</span>
                                            @endif
                                        </div>
                                        <div class="space-y-2">
                                            @foreach($day['appointments'] as $lead)
                                                <x-admin.calendar-event :lead="$lead" compact />
                                            @endforeach
                                        </div>
                                    </div>
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="space-y-6 p-5 lg:hidden">
            @foreach($calendar['agenda'] as $day)
                <section aria-labelledby="agenda-{{ $day['date']->format('Y-m-d') }}">
                    <h3 id="agenda-{{ $day['date']->format('Y-m-d') }}"
                        class="mb-3 flex flex-wrap items-center gap-2 text-base font-bold">
                        <time datetime="{{ $day['date']->format('Y-m-d') }}">
                            {{ $day['date']->locale('vi')->isoFormat('dddd, DD/MM') }}
                        </time>
                        @if($day['isToday'])
                            <x-admin.badge tone="success">Hôm nay</x-admin.badge>
                        @endif
                        <span class="text-sm font-normal text-muted">· {{ $day['appointments']->count() }} lịch</span>
                    </h3>
                    <div class="grid gap-3 sm:grid-cols-2">
                        @foreach($day['appointments'] as $lead)
                            <x-admin.calendar-event :lead="$lead" />
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>
        @if($calendar['total'] === 0)
            <div class="border-t border-line p-5 sm:p-6">
                <x-admin.empty-state title="Chưa có lịch lái thử trong tháng"
                    description="Chọn tháng khác hoặc đổi bộ lọc. Yêu cầu chưa có thời gian nằm trong danh sách." />
            </div>
        @endif
    </section>
    @if($calendar['unscheduled'] > 0)
        <div class="panel flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="mb-2!">{{ $calendar['unscheduled'] }} yêu cầu chưa có thời gian</h2>
                <p class="mb-0! text-muted">Mở danh sách để liên hệ khách và xếp lịch lái thử.</p>
            </div>
            <a class="button secondary"
                href="{{ route('admin.leads.index', array_merge($currentFilters, ['view' => 'list'])) }}">
                Xem yêu cầu lái thử <x-admin.icon name="arrow" />
            </a>
        </div>
    @endif
@endsection
