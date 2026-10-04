@props(['records'])
<div {{ $attributes->class(['table-block']) }}>
    {{ $slot }}
    <x-admin.pagination :records="$records" />
</div>
