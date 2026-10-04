@props(['label', 'column', 'defaultSort' => null, 'defaultDirection' => 'asc'])
@php
    $active = (request('sort') ?: $defaultSort) === $column;
    $direction = request('direction') ?: (request()->filled('sort') ? 'asc' : $defaultDirection);
    $nextDirection = $active && $direction === 'asc' ? 'desc' : 'asc';
    $sortUrl = request()->fullUrlWithQuery(['sort' => $column, 'direction' => $nextDirection, 'page' => null]);
    $sortLabel = 'Sắp xếp '.mb_strtolower($label).' '.($nextDirection === 'asc' ? 'tăng dần' : 'giảm dần');
    $icon = $active ? ($direction === 'asc' ? 'sort-asc' : 'sort-desc') : 'sort';
@endphp
<th scope="col" aria-sort="{{ $active ? ($direction === 'asc' ? 'ascending' : 'descending') : 'none' }}">
    <a class="column-sort" href="{{ $sortUrl }}" aria-label="{{ $sortLabel }}"
        data-active="{{ $active ? 'true' : 'false' }}" {{ $attributes }}>
        <span>{{ $label }}</span>
        <x-admin.icon :name="$icon" />
    </a>
</th>
