<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProjectRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorized via middleware
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'expires_at' => ['required', 'date'],
            'is_password_protected' => ['boolean'],
            'password' => ['nullable', 'required_if:is_password_protected,1', 'string', 'min:4'],
            'allow_download' => ['boolean'],
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'is_password_protected' => $this->has('is_password_protected'),
            'allow_download' => $this->has('allow_download'),
        ]);
    }
}
