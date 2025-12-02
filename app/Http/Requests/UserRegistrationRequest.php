<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Rules\UniqueNipAcrossTables;
use App\Rules\UniqueEmailAcrossTables;
use App\Rules\ValidNipFormat;

class UserRegistrationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Allow guest users to register
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
            'nip' => [
                'required',
                'string',
                new ValidNipFormat(),
                new UniqueNipAcrossTables(),
            ],
            'nama_lengkap' => [
                'required',
                'string',
                'min:3',
                'max:100',
                'regex:/^[a-zA-Z\s.\',\-]+$/'
            ],
            'email' => [
                'required',
                'email:rfc',
                'max:100',
                'regex:/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/',
                new UniqueEmailAcrossTables(),
            ],
            'password' => [
                'required',
                'confirmed',
                'min:8',
                'max:128',
                'regex:/^(?=.*[a-zA-Z])(?=.*\d)/'  // Only requires letters and numbers
            ],
            'jabatan' => [
                'required',
                'string',
                'min:2',
                'max:100',
                'regex:/^[a-zA-Z0-9\s.\',\-]+$/'
            ],
            'unit_kerja' => [
                'required',
                'string',
                'min:2',
                'max:100',
                'regex:/^[a-zA-Z0-9\s.\',\-]+$/'
            ],
            'no_telepon' => [
                'nullable',
                'string',
                'min:10',
                'max:20',
                'regex:/^(\+62|62|0)[0-9]{8,15}$/'
            ],
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
            'nip.required' => 'NIP wajib diisi',
            'nama_lengkap.required' => 'Nama lengkap wajib diisi',
            'nama_lengkap.min' => 'Nama lengkap minimal 3 karakter',
            'nama_lengkap.regex' => 'Nama hanya boleh berisi huruf, spasi, titik, koma, apostrof, dan tanda hubung',
            'email.required' => 'Email wajib diisi',
            'email.email' => 'Format email tidak valid',
            'email.max' => 'Email maksimal 100 karakter',
            'email.regex' => 'Format email tidak valid',
            'password.required' => 'Password wajib diisi',
            'password.confirmed' => 'Konfirmasi password tidak cocok',
            'password.min' => 'Password minimal 8 karakter',
            'password.max' => 'Password maksimal 128 karakter',
            'password.regex' => 'Password harus mengandung minimal 1 huruf dan 1 angka',
            'jabatan.required' => 'Jabatan wajib diisi',
            'jabatan.min' => 'Jabatan minimal 2 karakter',
            'jabatan.regex' => 'Jabatan hanya boleh berisi huruf, angka, spasi, titik, koma, apostrof, dan tanda hubung',
            'unit_kerja.required' => 'Unit kerja wajib diisi',
            'unit_kerja.min' => 'Unit kerja minimal 2 karakter',
            'unit_kerja.regex' => 'Unit kerja hanya boleh berisi huruf, angka, spasi, titik, koma, apostrof, dan tanda hubung',
            'no_telepon.min' => 'Nomor telepon minimal 10 digit',
            'no_telepon.max' => 'Nomor telepon maksimal 20 digit',
            'no_telepon.regex' => 'Format nomor telepon tidak valid. Gunakan format: 08xx, +628xx, atau 62xx',
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
            'nip' => 'NIP',
            'nama_lengkap' => 'nama lengkap',
            'email' => 'email',
            'password' => 'password',
            'jabatan' => 'jabatan',
            'unit_kerja' => 'unit kerja',
            'no_telepon' => 'nomor telepon',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Clean and format input data
        $this->merge([
            'nip' => trim($this->nip),
            'nama_lengkap' => trim(ucwords(strtolower($this->nama_lengkap))),
            'email' => trim(strtolower($this->email)),
            'jabatan' => trim(ucwords(strtolower($this->jabatan))),
            'unit_kerja' => trim(ucwords(strtolower($this->unit_kerja))),
            'no_telepon' => trim($this->no_telepon),
        ]);
    }
}