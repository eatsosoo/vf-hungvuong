@extends('layouts.admin')
@section('title', 'Chăm sóc khách hàng')
@section('content')
    <x-admin.page-heading :title="$lead->name.' · #'.$lead->id" />
    <div class="editor-grid">
        <section class="panel">
            <h2>
                Thông tin yêu cầu
            </h2>
            <p>
                {{ __('studio.type.'.$lead->type) }}
                ·
                {{ $lead->phone }}
                ·
                {{ $lead->email }}
            </p>
            <p>
                {{ $lead->vehicle?->name }}
                {{ $lead->variant?->name }}
            </p>
            <p>
                {{ $lead->message }}
            </p>
            <p>
                Nguồn:
                {{ $lead->source }}
                · Lịch mong muốn:
                {{ $lead->preferred_at?->format('d/m/Y H:i') }}
            </p>
            <h2>
                Lịch sử chăm sóc
            </h2>
            @forelse($lead->notes as $note)
                <article class="note">
                    <strong>
                        {{ $note->author->name }}
                    </strong>
                    ·
                    {{ $note->created_at->format('d/m/Y H:i') }}
                    <p>
                        {{ $note->body }}
                    </p>
                </article>
            @empty
                <p>
                    Chưa có ghi chú.
                </p>
            @endforelse
        </section>
        <form class="panel" method="post" action="{{ route('admin.leads.update', $lead) }}">
            @csrf
            @method('PUT')
            <x-select name="status" label="Trạng thái">
                @foreach(App\Enums\LeadStatus::forType($lead->type) as $status)
                    <option
                        value="{{ $status->value }}"
                        @selected(old('status', $lead->status->value) === $status->value)
                    >
                        {{ __('studio.status.'.$status->value) }}
                    </option>
                @endforeach
            </x-select>
            @can('assign', App\Models\Lead::class)
                <x-select name="assigned_to" label="Nhân viên phụ trách">
                    <option value="">
                        Chưa phân công
                    </option>
                    @foreach($staff as $user)
                        <option
                            value="{{ $user->id }}"
                            @selected(old('assigned_to', $lead->assigned_to) == $user->id)
                        >
                            {{ $user->name }}
                        </option>
                    @endforeach
                </x-select>
            @endcan
            <x-field
                name="appointment_at"
                label="Lịch hẹn xác nhận"
                type="datetime-local"
                :value="$lead->appointment_at?->format('Y-m-d\TH:i')" />
            <x-field name="location" label="Địa điểm lái thử" :value="$lead->location" />
            <x-field name="quote_amount" label="Giá báo VNĐ" type="number" :value="$lead->quote_amount" />
            <x-field
                name="quote_details"
                label="Nội dung báo giá"
                type="textarea"
                :value="$lead->quote_details" />
            <x-field name="note" label="Thêm ghi chú chăm sóc" type="textarea" />
            <button>
                Lưu xử lý
            </button>
        </form>
    </div>
@endsection
