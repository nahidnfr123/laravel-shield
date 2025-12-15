<?php

namespace NahidFerdous\Shield\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class RoleRequest extends FormRequest
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
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string',
            'guard_name' => [
                'sometimes',
                'nullable',
                'string',
                'in:'.implode(',', array_keys(config('auth.guards'))),
            ],
        ];
    }

    //    public function attributes(): array
    //    {
    //        return [
    //            'name' => __('shield::messages.role.name'),
    //        ];
    //    }
}
