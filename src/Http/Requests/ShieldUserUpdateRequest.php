<?php

namespace NahidFerdous\Shield\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ShieldUserUpdateRequest extends FormRequest
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
        return [
            'name' => 'required|string|min:3|max:60',
            'email' => 'required|email',
            'password' => 'required|min:6|confirmed',
        ];
    }
}
