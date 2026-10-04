@props(['records', 'pageSizes' => [10, 20, 50, 100], 'showPageSize' => true])
@php
    $currentPage = $records->currentPage();
    $visiblePage = max(1, min($currentPage, $records->lastPage()));
    $pages = array_unique([
        1,
        ...range(max(1, $visiblePage - 1), min($records->lastPage(), $visiblePage + 1)),
        $records->lastPage(),
    ]);
    $previousPage = 0;
    $pageName = $records->getPageName();
    $paginationId = 'pagination-'.$pageName;
    $pageSizes = array_unique([...$pageSizes, $records->perPage()]);
    sort($pageSizes);
@endphp
<div class="table-pagination">
    <div class="pagination-info">
        <p>
            Hiển thị {{ $records->firstItem() ?? 0 }}–{{ $records->lastItem() ?? 0 }}
            trong <strong>{{ number_format($records->total(), 0, ',', '.') }}</strong> kết quả
        </p>
        @if($showPageSize)
            <form method="get" action="{{ url()->current() }}" class="pagination-size" data-page-size-form>
                @foreach(Illuminate\Support\Arr::dot(request()->except([$pageName, 'per_page'])) as $key => $value)
                    @php
                        $parts = explode('.', $key);
                        $queryName = array_shift($parts);
                        foreach ($parts as $part) {
                            $queryName .= '['.$part.']';
                        }
                    @endphp
                    <input type="hidden" name="{{ $queryName }}" value="{{ $value }}">
                @endforeach
                <label for="{{ $paginationId }}-size">Số dòng / trang</label>
                <select id="{{ $paginationId }}-size" name="per_page" data-page-size>
                    @foreach($pageSizes as $pageSize)
                        <option value="{{ $pageSize }}" @selected($records->perPage() === $pageSize)>
                            {{ $pageSize }}
                        </option>
                    @endforeach
                </select>
                <button type="submit" class="secondary" data-page-size-submit>Áp dụng</button>
            </form>
        @endif
    </div>
    <nav aria-label="Phân trang" class="pagination-pages">
        @if($records->onFirstPage())
            <span class="pagination-disabled" aria-disabled="true">Trước</span>
        @else
            <a class="button secondary" rel="prev"
                href="{{ $records->url(max(1, min($currentPage - 1, $records->lastPage()))) }}">Trước</a>
        @endif
        @foreach($pages as $page)
            @if($page - $previousPage > 1)
                <span class="pagination-gap" aria-hidden="true">…</span>
            @endif
            @if($page === $currentPage)
                <span class="pagination-current" aria-current="page" aria-label="Trang {{ $page }}">
                    {{ $page }}
                </span>
            @else
                <a class="button secondary pagination-number" href="{{ $records->url($page) }}"
                    aria-label="Trang {{ $page }}">{{ $page }}</a>
            @endif
            @php($previousPage = $page)
        @endforeach
        @if($records->hasMorePages())
            <a class="button secondary" href="{{ $records->nextPageUrl() }}" rel="next">Sau</a>
        @else
            <span class="pagination-disabled" aria-disabled="true">Sau</span>
        @endif
    </nav>
</div>
