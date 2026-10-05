@props(['rows', 'from' => null, 'to' => null])
@if($rows->isEmpty())
    <x-admin.empty-state title="Chưa có dữ liệu trong khoảng lọc"
        description="Chọn khoảng ngày khác để xem xu hướng khách hàng." />
@else
    @php
        $values = $rows->pluck('total', 'day')->map(fn (mixed $value): int => (int) $value)->all();
        $start = strtotime(($from ?: $rows->first()->day).' UTC');
        $end = strtotime(($to ?: $rows->last()->day).' UTC');
        $values[gmdate('Y-m-d', $start)] ??= 0;
        $values[gmdate('Y-m-d', $end)] ??= 0;
        ksort($values);
        $samples = [];
        $previousDay = null;
        foreach ($values as $day => $count) {
            $timestamp = strtotime($day.' UTC');
            if ($previousDay !== null && $timestamp - $previousDay > 86400) {
                $samples[$previousDay + 86400] = 0;
                $samples[$timestamp - 86400] = 0;
            }
            $samples[$timestamp] = $count;
            $previousDay = $timestamp;
        }
        ksort($samples);
        $step = max(1, (int) ceil(max($values) / 4));
        $ceiling = $step * 4;
        $coordinates = [];
        foreach ($samples as $timestamp => $count) {
            $x = $end === $start ? 480 : ($timestamp - $start) / ($end - $start) * 960;
            $y = 260 - $count / $ceiling * 240;
            $coordinates[] = round($x, 2).','.round($y, 2);
        }
        $points = implode(' ', $coordinates);
        $firstX = $end === $start ? 480 : 0;
        $lastX = $end === $start ? 480 : 960;
    @endphp
    <div class="daily-lead-chart" data-daily-chart data-values="{{ json_encode($values) }}"
        data-start="{{ $start }}" data-end="{{ $end }}" data-ceiling="{{ $ceiling }}"
        tabindex="0" role="group" aria-label="Biểu đồ khách theo ngày"
        aria-describedby="daily-chart-help daily-chart-value">
        <p class="field-hint mb-4" id="daily-chart-help">
            Rê chuột hoặc chạm vào biểu đồ để xem số khách.
            Dùng phím trái/phải để chuyển ngày, Home/End để đến đầu/cuối.
        </p>
        <div class="daily-chart-plot">
            <div class="daily-chart-y-axis" aria-hidden="true">
                @for($tick = 4; $tick >= 0; $tick--)
                    <span>{{ number_format($tick * $step, 0, ',', '.') }}</span>
                @endfor
            </div>
            <svg viewBox="0 0 960 280" preserveAspectRatio="none" data-chart-plot
                role="img" aria-label="Đường biểu diễn số khách mới mỗi ngày">
                @for($tick = 0; $tick <= 4; $tick++)
                    <line class="daily-chart-grid" x1="0" x2="960" y1="{{ 20 + $tick * 60 }}"
                        y2="{{ 20 + $tick * 60 }}" />
                @endfor
                <polygon class="daily-chart-area" points="{{ $firstX }},260 {{ $points }} {{ $lastX }},260" />
                <polyline class="daily-chart-line" points="{{ $points }}" vector-effect="non-scaling-stroke" />
                @if(count($coordinates) === 1)
                    <circle class="daily-chart-point" cx="480" cy="{{ 260 - max($values) / $ceiling * 240 }}" r="5" />
                @endif
                <g data-chart-cursor hidden>
                    <line class="daily-chart-cursor" data-chart-guide x1="0" x2="0" y1="20" y2="260" />
                    <circle class="daily-chart-point" data-chart-point cx="0" cy="260" r="5" />
                </g>
            </svg>
        </div>
        <div class="daily-chart-x-axis" aria-hidden="true">
            <span>{{ gmdate('d/m/Y', $start) }}</span>
            @if($end !== $start)
                <span>{{ gmdate('d/m/Y', $end) }}</span>
            @endif
        </div>
        <p class="daily-chart-value" id="daily-chart-value" data-chart-value role="status" aria-live="polite">
            <span class="size-2.5 rounded-full bg-sky-700" aria-hidden="true"></span>
            <span data-chart-value-text>Số khách mới / ngày</span>
        </p>
    </div>
@endif
