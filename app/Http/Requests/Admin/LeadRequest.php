<?php

namespace App\Http\Requests\Admin;

use App\Enums\LeadStatus;
use App\Models\Lead;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('lead'));
    }

    public function rules(): array
    {
        $lead = $this->route('lead');
        $appointmentRules = ['nullable', 'date'];
        $existing = $lead->appointment_at?->format('Y-m-d\TH:i');
        if ($this->input('appointment_at') && $this->input('appointment_at') !== $existing) {
            $appointmentRules[] = 'after:now';
        }
        $appointmentRules[] = Rule::requiredIf($lead->type === 'test_drive' && $this->input('status') === 'confirmed');

        return [
            'status' => ['required', Rule::enum(LeadStatus::class),
                Rule::in(array_map(
                    fn (LeadStatus $status): string => $status->value,
                    LeadStatus::forType($lead->type)
                ))],
            'assigned_to' => [$this->user()->can('assign', Lead::class) ? 'nullable' : 'prohibited',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('is_active', true)
                    ->whereIn('role', ['admin', 'manager', 'sales']))],
            'appointment_at' => $appointmentRules,
            'location' => ['nullable', 'string', 'max:255'],
            'quote_amount' => ['nullable', 'integer', 'min:0', 'max:999999999999999'],
            'quote_details' => ['nullable', 'string', 'max:5000'], 'note' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
