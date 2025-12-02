<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use App\Services\AuthService;

class UserUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Only admin helpdesk can update users
        $authService = app(AuthService::class);
        $user = Auth::user();
        return $user && $authService->getUserRole($user) === 'admin_helpdesk';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $userId = $this->route('user');

        return [
            'name' => 'required|string|max:255|regex:/^[a-zA-Z\s]+$/',
            'email' => 'required|email:rfc,dns|unique:users,email,' . $userId . ',nip|max:255',
            'phone' => 'nullable|string|max:20|regex:/^[\+0-9\-\(\)\s]+$/',
            'department' => 'nullable|string|max:100',
            'position' => 'nullable|string|max:100',
            'status' => 'required|in:active,inactive',
            'password' => 'nullable|string|min:8|confirmed|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Full name is required',
            'name.regex' => 'Name can only contain letters and spaces',
            'email.required' => 'Email address is required',
            'email.email' => 'Please enter a valid email address',
            'email.unique' => 'This email address is already registered',
            'phone.regex' => 'Phone number format is invalid',
            'password.min' => 'Password must be at least 8 characters long',
            'password.confirmed' => 'Password confirmation does not match',
            'password.regex' => 'Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character',
            'status.required' => 'Status is required',
            'status.in' => 'Status must be either active or inactive',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'full name',
            'email' => 'email address',
            'phone' => 'phone number',
            'department' => 'department',
            'position' => 'position',
            'password' => 'password',
            'status' => 'account status',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Clean and format input data
        $this->merge([
            'name' => trim(ucwords(strtolower($this->name))),
            'email' => trim(strtolower($this->email)),
            'phone' => trim($this->phone),
            'department' => trim(ucwords(strtolower($this->department))),
            'position' => trim(ucwords(strtolower($this->position))),
        ]);
    }
}