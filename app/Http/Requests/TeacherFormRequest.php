<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TeacherFormRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $this->teacher?->user_id,
            'password' => 'nullable|string|min:6',
            'nip' => 'required|string|unique:teachers,nip,' . $this->teacher?->id,
            'address' => 'nullable|string',
            'phone' => 'nullable|string'
        ];
    }
}
