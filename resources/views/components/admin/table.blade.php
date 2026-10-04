@props(['caption'])
<div class="table-wrap" role="region" aria-label="{{ $caption }}" tabindex="0">
    <table>
        <caption class="sr-only">{{ $caption }}</caption>
        {{ $slot }}
    </table>
</div>
