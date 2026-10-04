@props(['assessment'])
<section class="seo-assessment" data-seo-assessment aria-labelledby="seo-assessment-title">
    <div class="flex items-start justify-between gap-4">
        <div>
            <p class="eyebrow mb-2!">Chất lượng bài viết</p>
            <h2 id="seo-assessment-title" class="mb-2! text-lg!">Kiểm tra SEO</h2>
        </div>
        <div class="seo-score" data-seo-score>
            <strong data-score-value>{{ $assessment['score'] }}</strong><span>/100</span>
        </div>
    </div>
    <p class="text-sm font-semibold" data-score-label role="status">{{ $assessment['label'] }}</p>
    <p class="mt-2 text-xs leading-6 text-muted">
        Bộ tiêu chí nội bộ, không phải điểm của Google hay cam kết thứ hạng.
        Điểm cao vẫn cần kiểm tra tính chính xác và giá trị cho người đọc.
    </p>
    <ul class="seo-checks">
        @foreach($assessment['checks'] as $check)
            <li data-seo-check="{{ $check['key'] }}" data-passed="{{ $check['passed'] ? 'true' : 'false' }}">
                <div class="flex items-start justify-between gap-3">
                    <span class="flex items-center gap-2 font-semibold">
                        <span data-check-indicator>{{ $check['passed'] ? '✓' : '○' }}</span>
                        {{ $check['label'] }}
                    </span>
                    <span class="shrink-0 text-xs" data-check-points>{{ $check['points'] }}/{{ $check['max'] }}</span>
                </div>
                <p class="mt-2 mb-0! text-xs leading-6 text-muted">{{ $check['advice'] }}</p>
            </li>
        @endforeach
    </ul>
</section>
