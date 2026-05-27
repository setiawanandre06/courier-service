<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCourierRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|string|email|max:255|unique:couriers,email,' . $this->route('id'),
            'phone_number' => 'sometimes|required|string|max:20|unique:couriers,phone_number,' . $this->route('id'),
            'vehicle_type' => 'sometimes|required|string|max:50',
            'vehicle_plate' => 'sometimes|required|string|max:20|unique:couriers,vehicle_plate,' . $this->route('id'),
            'level' => 'sometimes|required|integer|min:1|max:5',
        ];
    }
}
