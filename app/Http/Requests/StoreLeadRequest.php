<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(['consultation', 'quote', 'test_drive'])],
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'regex:/\A\+?[0-9 ()-]{9,20}\z/'],
            'email' => ['nullable', 'email', 'max:254'], 'message' => ['nullable', 'string', 'max:2000'],
            'vehicle_id' => ['nullable', Rule::exists('vehicles', 'id')->where('is_active', true)],
            'vehicle_variant_id' => ['nullable', Rule::exists('vehicle_variants', 'id')
                ->where('vehicle_id', $this->integer('vehicle_id'))],
            'source' => ['nullable', 'string', 'max:100', 'regex:/\A[a-zA-Z0-9_-]+\z/'],
            'preferred_at' => ['nullable', 'required_if:type,test_drive', 'date', 'after:now'],
            'consent' => ['accepted'], 'website' => ['prohibited'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'name' => __('Họ và tên'), 'phone' => __('Số điện thoại'), 'email' => 'Email',
            'vehicle_id' => __('Mẫu xe quan tâm'), 'vehicle_variant_id' => __('Phiên bản'),
            'preferred_at' => __('Ngày và giờ mong muốn'), 'message' => __('Nhu cầu của bạn'),
            'consent' => __('Chính sách bảo mật'),
        ];
    }

    public function messages(): array
    {
        return [
            'required' => __('Vui lòng nhập :attribute.'),
            'email' => __(':attribute phải là địa chỉ email hợp lệ.'),
            'exists' => __(':attribute đã chọn không hợp lệ.'),
            'date' => __(':attribute phải là ngày hợp lệ.'),
            'accepted' => __('Bạn cần đồng ý với :attribute.'),
            'max.string' => __(':attribute không được quá :max ký tự.'),
            'string' => __(':attribute phải là văn bản.'),
            'in' => __(':attribute đã chọn không hợp lệ.'),
            'preferred_at.required_if' => __('Vui lòng chọn ngày và giờ lái thử.'),
            'consent.accepted' => __('Bạn cần đồng ý cho đại lý liên hệ.'),
            'phone.regex' => __('Số điện thoại không hợp lệ.'),
            'preferred_at.after' => __('Vui lòng chọn thời gian trong tương lai.')];
    }
}
