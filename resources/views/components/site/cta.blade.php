@props([
    'title' => __('Cùng bạn chọn chiếc xe phù hợp.'),
    'description' => __('Trao đổi cùng đội ngũ VinFast Hùng Vương và trải nghiệm xe điện trước khi quyết định.'),
    'vehicle' => null,
])
<section class="rounded-3xl bg-night p-7 text-white sm:p-10">
    <div class="grid items-center gap-7 lg:grid-cols-[minmax(0,1fr)_auto]">
        <div>
            <p class="client-eyebrow text-brand">{{ __('VinFast Hùng Vương') }}</p>
            <h2 class="mt-3">{{ $title }}</h2>
            <p class="mt-4 max-w-2xl text-white/70">{{ $description }}</p>
        </div>
        <div class="flex flex-wrap gap-3 lg:max-w-52">
            <a class="client-button lg:w-full"

                    href="{{
                        route($clientRoutePrefix.'contact',
                         array_filter(['type' => 'quote',
                         'vehicle' => $vehicle]))
                    }}">
                {{ __('Nhận báo giá') }} <x-admin.icon name="arrow" class="size-4" />
            </a>
            <a class="client-button border-white/25 bg-transparent text-white hover:bg-white/10 lg:w-full"
                data-test-drive-open

                    href="{{
                        route($clientRoutePrefix.'contact',
                         array_filter(['type' => 'test_drive',
                         'vehicle' => $vehicle]))
                    }}">
                {{ __('Đặt lịch lái thử') }}
            </a>
        </div>
    </div>
</section>
