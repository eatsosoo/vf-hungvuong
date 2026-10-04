@props(['tone' => 'neutral'])
@php
    $tones = [
        'neutral' => 'border-line bg-paper text-muted',
        'success' => 'border-green-200 bg-green-50 text-green-900',
        'warning' => 'border-amber-200 bg-amber-50 text-amber-900',
        'danger' => 'border-red-200 bg-red-50 text-red-800',
        'info' => 'border-sky-200 bg-sky-50 text-sky-900',
    ];
@endphp
<span {{ $attributes->class(['badge', $tones[$tone] ?? $tones['neutral']]) }}>{{ $slot }}</span>
