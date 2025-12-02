<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use App\Services\AuthService;

class UserStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Only admin helpdesk can create users
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
        return [
            'nip' => 'required|string|unique:users,nip|max:20|regex:/^[A-Za-z0-9]+$/',
            'name' => 'required|string|max:255|regex:/^[a-zA-Z\s]+$/', // Only letters and spaces
            'email' => 'required|email:rfc,dns|unique:users,email|max:255',
            'phone' => 'nullable|string|max:20|regex:/^[\+0-9\-\(\)\s]+$/',
            'department' => 'nullable|string|max:100',
            'position' => 'nullable|string|max:100',
            'password' => 'required|string|min:8|confirmed|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/',
            'status' => 'required|in:active,inactive',
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
            'nip.required' => 'Employee ID (NIP) is required',
            'nip.unique' => 'This Employee ID is already registered',
            'nip.regex' => 'Employee ID can only contain letters and numbers',
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
            'nip' => 'Employee ID',
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
            'nip' => trim(strtoupper($this->nip)),
            'name' => trim(ucwords(strtolower($this->name))),
            'email' => trim(strtolower($this->email)),
            'phone' => trim($this->phone),
            'department' => trim(ucwords(strtolower($this->department))),
            'position' => trim(ucwords(strtolower($this->position))),
        ]);
    }
}