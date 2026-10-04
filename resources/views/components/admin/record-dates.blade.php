@props(['record'])
@foreach(['created_at', 'updated_at'] as $timestamp)
    <td class="table-secondary table-date">
        @if($record->{$timestamp})
            <time datetime="{{ $record->{$timestamp}->toIso8601String() }}">
                {{ $record->{$timestamp}->format('d/m/Y H:i') }}
            </time>
        @else
            —
        @endif
    </td>
@endforeach
