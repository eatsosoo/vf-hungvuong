@props(['name' => 'arrow'])
@php
    $paths = [
        'dashboard' => 'M3 3h7v7H3z M14 3h7v7h-7z M3 14h7v7H3z M14 14h7v7h-7z',
        'car' => 'm5 7 2-4h10l2 4 M3 10l2-3h14l2 3v8H3z M7 18v3 M17 18v3 M6 12h2 M16 12h2',
        'tag' => 'M3 3h8l10 10-8 8L3 11z M7 7h.01',
        'file' => 'M14 2H4v20h16V8z M14 2v6h6 M8 12h8 M8 16h6',
        'folder' => 'M3 5h6l2 2h10v14H3z',
        'image' => 'M3 3h18v18H3z M3 17l5-5 4 4 4-6 5 7 M8 7h.01',
        'users' => 'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2 M22 21v-2a4 4 0 0 0-3-3.87 '
            .'M12 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0 M16 3a4 4 0 0 1 0 8',
        'calendar' => 'M4 5h16v16H4z M8 3v4 M16 3v4 M4 10h16 M8 14h3',
        'chart' => 'M3 3v18h18 M7 16v-4 M12 16V8 M17 16V5',
        'settings' => 'M4 6h16 M4 12h16 M4 18h16 M8 3v6 M16 9v6 M10 15v6',
        'history' => 'M3 3v5h5 M3 8a9 9 0 1 1 0 8 M12 7v5l3 2',
        'lock' => 'M5 10h14v11H5z M8 10V7a4 4 0 0 1 8 0v3 M12 14v3',
        'external' => 'M14 3h7v7 M21 3 10 14 M10 3H3v18h18v-7',
        'logout' => 'M9 3H3v18h6 M9 12h12 M17 8l4 4-4 4',
        'menu' => 'M4 6h16 M4 12h16 M4 18h16',
        'arrow' => 'M5 12h14 M13 6l6 6-6 6',
        'edit' => 'm16 3 5 5 M3 21l5-1L21 7l-5-5L3 16z',
        'trash' => 'M3 6h18 M9 6V3h6v3 M5 6l1 15h12l1-15 M10 10v7 M14 10v7',
        'save' => 'M4 3h13l4 4v14H3V3z M7 3v6h10V3 M7 21v-8h10v8',
        'plus' => 'M12 5v14 M5 12h14',
        'sort' => 'M8 20V4 M4 8l4-4 4 4 M16 4v16 M12 16l4 4 4-4',
        'sort-asc' => 'M12 20V4 M6 10l6-6 6 6',
        'sort-desc' => 'M12 4v16 M6 14l6 6 6-6',
        'bell' => 'M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9 M10 21h4',
        'check' => 'm5 12 4 4L19 6',
        'inbox' => 'm3 3-2 13v5h22v-5L21 3z M1 16h6l2 3h6l2-3h6',
        'panel' => 'M3 3h18v18H3z M9 3v18 M5 7h1 M5 11h1 M5 15h1',
        'bold' => 'M6 4h7a4 4 0 0 1 0 8H6z M6 12h8a4 4 0 0 1 0 8H6z',
        'italic' => 'M11 4h8 M5 20h8 M15 4 9 20',
        'strike' => 'M17 5c-2-3-10-3-10 1 0 2 2 3 5 4 M4 12h16 M7 19c3 3 10 2 10-2 0-2-1-3-3-3',
        'list' => 'M9 6h12 M9 12h12 M9 18h12 M3 6h.01 M3 12h.01 M3 18h.01',
        'ordered-list' => 'M10 6h11 M10 12h11 M10 18h11 M3 4h2v5 M3 14c4-3 4 1 0 4h3',
        'quote' => 'M3 5h7v7H3z M10 12c0 4-3 7-6 7 M14 5h7v7h-7z M21 12c0 4-3 7-6 7',
        'link' => 'M10 13a5 5 0 0 0 7 0l3-3a5 5 0 0 0-7-7l-2 2 M14 11a5 5 0 0 0-7 0l-3 3a5 5 0 0 0 7 7l2-2',
        'unlink' => 'M7 7 3 11a5 5 0 0 0 7 7l2-2 M17 17l4-4a5 5 0 0 0-7-7l-2 2 M2 2l20 20',
        'table' => 'M3 3h18v18H3z M3 9h18 M3 15h18 M9 9v12 M15 9v12',
        'undo' => 'M4 4v6h6 M4 10a8 8 0 1 1-1 7',
        'redo' => 'M20 4v6h-6 M20 10a8 8 0 1 0 1 7',
        'code' => 'm8 5-7 7 7 7 M16 5l7 7-7 7 M14 3l-4 18',
        'eye' => 'M2 12s4-7 10-7 10 7 10 7-4 7-10 7-10-7-10-7 M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0',
        'video' => 'M3 4h18v16H3z m7 4 6 4-6 4z',
        'expand' => 'M8 3H3v5 M16 3h5v5 M21 16v5h-5 M8 21H3v-5',
        'close' => 'm6 6 12 12 M18 6 6 18',
    ];
@endphp
<svg {{ $attributes->class(['size-5 shrink-0']) }} viewBox="0 0 24 24" fill="none"
    stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    <path d="{{ $paths[$name] ?? $paths['arrow'] }}" />
</svg>
