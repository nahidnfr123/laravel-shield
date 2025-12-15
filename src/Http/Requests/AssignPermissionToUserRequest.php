<?php

namespace NahidFerdous\Shield\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignPermissionToUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->user()->can('assign-permission');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user_id' => 'required|string',
            'guard' => [
                'sometimes',
                'string',
                Rule::in(array_keys(config('auth.guards'))),
            ],
            'permissions' => 'sometimes|required|array',
            'permissions.*' => 'integer|exists:permissions,id',
        ];
    }
}
