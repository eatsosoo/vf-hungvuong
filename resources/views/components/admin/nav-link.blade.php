@props(['href', 'active' => false, 'icon' => 'arrow'])
<a href="{{ $href }}" class="nav-link" title="{{ trim(strip_tags((string) $slot)) }}"
    @if($active) aria-current="page" @endif>
    <x-admin.icon :name="$icon" />
    <span class="nav-label">{{ $slot }}</span>
</a>
