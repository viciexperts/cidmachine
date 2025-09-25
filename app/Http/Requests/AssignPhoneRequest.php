<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignPhoneRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return  [
            'phone'       => ['required', 'string', 'max:20'],
            'campaign_id' => ['required', 'string', 'max:20'],
            // optional overrides—if you want to pass them
            'area_code'   => ['nullable', 'string', 'max:10'],
            'caller_id'   => ['nullable', 'string', 'max:10'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $phone = preg_replace('/\D+/', '', (string)($this->input('phone') ?? ''));
        if ($phone && strlen($phone) >= 10) {
            $this->merge([
                'normalized_phone' => $phone,
                'area_code_guess'  => substr($phone, 0, 3),
                'caller_id_guess'  => substr($phone, -10),
            ]);
        }
    }
}
