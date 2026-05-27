<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreCourierRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:couriers',
            'phone_number' => 'required|string|max:20|unique:couriers',
            'vehicle_type' => 'required|string|max:50',
            'vehicle_plate' => 'required|string|max:20|unique:couriers',
            'level' => 'required|integer|min:1|max:5',
        ];
    }
}
