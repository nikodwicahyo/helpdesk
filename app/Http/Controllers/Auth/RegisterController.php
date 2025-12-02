<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Http\Requests\UserRegistrationRequest;
use App\Rules\ValidNipFormat;
use App\Rules\UniqueNipAcrossTables;
use App\Rules\UniqueEmailAcrossTables;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;

class RegisterController extends Controller
{
    /**
     * Show registration form
     */
    public function showRegistrationForm(): Response
    {
        return Inertia::render('Register');
    }

    /**
     * Handle registration request
     */
    public function register(Request $request): RedirectResponse
    {
        // Manual validation to ensure proper handling
        $validator = Validator::make($request->all(), (new UserRegistrationRequest())->rules(), (new UserRegistrationRequest())->messages());

        if ($validator->fails()) {
            return redirect()
                ->back()
                ->withErrors($validator)
                ->withInput($request->except('password', 'password_confirmation'));
        }

        $validated = $validator->validated();

        // Log successful registration
        Log::info('New user registered', [
            'nip' => $validated['nip'],
            'email' => $validated['email'],
            'nama_lengkap' => $validated['nama_lengkap'],
            'ip_address' => $request->ip(),
            'timestamp' => now()->toISOString()
        ]);

        try {
            // Create new user with proper role assignment
            $user = User::create([
                'nip' => $validated['nip'],
                'name' => $validated['nama_lengkap'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'position' => $validated['jabatan'],
                'department' => $validated['unit_kerja'],
                'phone' => $validated['no_telepon'] ?? null,
                'status' => 'active', // Ensure status is set
                'email_verified_at' => null, // Can be verified later if needed
            ]);

            // Log successful registration
            Log::info('New user registered', [
                'nip' => $user->nip,
                'email' => $user->email,
                'nama_lengkap' => $user->nama_lengkap,
                'ip_address' => $request->ip(),
                'timestamp' => now()->toISOString()
            ]);

            return redirect()
                ->route('login')
                ->with('success', 'Registrasi berhasil! Silakan login dengan NIP dan password Anda.');

        } catch (\Exception $e) {
            Log::error('Registration failed', [
                'error' => $e->getMessage(),
                'nip' => $validated['nip'] ?? 'unknown',
                'email' => $validated['email'] ?? 'unknown',
                'ip_address' => $request->ip()
            ]);

            return back()
                ->withErrors(['error' => 'Terjadi kesalahan saat registrasi. Silakan coba lagi.'])
                ->withInput($request->except('password', 'password_confirmation'));
        }
    }

    /**
     * Check if NIP is available and valid
     */
    public function checkNip(Request $request): JsonResponse
    {
        $nip = $request->input('nip');

        if (empty($nip)) {
            return response()->json([
                'valid' => false,
                'available' => true,
                'message' => 'NIP wajib diisi',
                'suggestions' => []
            ]);
        }

        // Clean and normalize NIP
        $cleanedNip = preg_replace('/\D/', '', $nip);

        // Validate NIP format using custom rule
        $validator = Validator::make(['nip' => $cleanedNip], [
            'nip' => ['required', 'string', new ValidNipFormat()]
        ]);

        if ($validator->fails()) {
            return response()->json([
                'valid' => false,
                'available' => false,
                'message' => $validator->errors()->first(),
                'suggestions' => [
                    'Pastikan NIP 18 digit',
                    'Gunakan format NIP yang benar',
                    'Periksa kembali angka NIP Anda'
                ]
            ]);
        }

        // Check uniqueness across all role tables
        $validator = Validator::make(['nip' => $cleanedNip], [
            'nip' => [new UniqueNipAcrossTables()]
        ]);

        if ($validator->fails()) {
            return response()->json([
                'valid' => true,
                'available' => false,
                'message' => $validator->errors()->first(),
                'suggestions' => [
                    'NIP sudah terdaftar dalam sistem',
                    'Gunakan NIP yang berbeda',
                    'Hubungi administrator jika ada masalah'
                ]
            ]);
        }

        return response()->json([
            'valid' => true,
            'available' => true,
            'message' => 'NIP valid dan tersedia',
            'suggestions' => []
        ]);
    }

    /**
     * Check if email is available and valid
     */
    public function checkEmail(Request $request): JsonResponse
    {
        $email = $request->input('email');

        if (empty($email)) {
            return response()->json([
                'valid' => false,
                'available' => true,
                'message' => 'Email wajib diisi',
                'suggestions' => []
            ]);
        }

        // Clean and normalize email
        $cleanedEmail = strtolower(trim($email));

        // Validate email format
        $validator = Validator::make(['email' => $cleanedEmail], [
            'email' => [
                'required',
                'email:rfc',
                'max:100',
                'regex:/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/'
            ]
        ]);

        if ($validator->fails()) {
            return response()->json([
                'valid' => false,
                'available' => false,
                'message' => $validator->errors()->first(),
                'suggestions' => [
                    'Periksa format email yang dimasukkan',
                    'Gunakan email yang valid (contoh: nama@domain.com)',
                    'Pastikan domain email aktif'
                ]
            ]);
        }

        // Check uniqueness across all role tables
        $validator = Validator::make(['email' => $cleanedEmail], [
            'email' => [new UniqueEmailAcrossTables()]
        ]);

        if ($validator->fails()) {
            return response()->json([
                'valid' => true,
                'available' => false,
                'message' => $validator->errors()->first(),
                'suggestions' => [
                    'Email sudah terdaftar dalam sistem',
                    'Gunakan email yang berbeda',
                    'Hubungi administrator jika lupa password'
                ]
            ]);
        }

        return response()->json([
            'valid' => true,
            'available' => true,
            'message' => 'Email valid dan tersedia',
            'suggestions' => []
        ]);
    }

    /**
     * Check password strength
     */
    public function checkPassword(Request $request): JsonResponse
    {
        $password = $request->input('password');
        $passwordConfirmation = $request->input('password_confirmation');

        if (empty($password)) {
            return response()->json([
                'valid' => false,
                'strength' => 0,
                'message' => 'Password wajib diisi',
                'suggestions' => [
                    'Masukkan password untuk registrasi'
                ],
                'checks' => [
                    'length' => false,
                    'lowercase' => false,
                    'uppercase' => false,
                    'number' => false,
                    'symbol' => false,
                    'match' => false
                ]
            ]);
        }

        $checks = [
            'length' => strlen($password) >= 8,
            'lowercase' => preg_match('/[a-z]/', $password),
            'uppercase' => preg_match('/[A-Z]/', $password),
            'number' => preg_match('/\d/', $password),
            'symbol' => preg_match('/[@$!%*?&()_+\-=\[\]{};:"\\|,.<>\/?]/', $password),
            'match' => !empty($passwordConfirmation) && $password === $passwordConfirmation
        ];

        $strength = array_sum($checks);

        if ($strength < 5) {
            $message = 'Password lemah. Tambahkan ';
            $suggestions = [];

            if (!$checks['length']) {
                $suggestions[] = 'minimal 8 karakter';
            }
            if (!$checks['lowercase']) {
                $suggestions[] = 'huruf kecil';
            }
            if (!$checks['uppercase']) {
                $suggestions[] = 'huruf besar';
            }
            if (!$checks['number']) {
                $suggestions[] = 'angka';
            }
            if (!$checks['symbol']) {
                $suggestions[] = 'simbol';
            }

            $message .= implode(', ', $suggestions);

            return response()->json([
                'valid' => false,
                'strength' => $strength,
                'message' => $message,
                'suggestions' => $suggestions,
                'checks' => $checks
            ]);
        }

        if (!$checks['match'] && !empty($passwordConfirmation)) {
            return response()->json([
                'valid' => false,
                'strength' => $strength,
                'message' => 'Konfirmasi password tidak cocok',
                'suggestions' => [
                    'Pastikan konfirmasi password sama dengan password'
                ],
                'checks' => $checks
            ]);
        }

        return response()->json([
            'valid' => true,
            'strength' => $strength,
            'message' => 'Password kuat',
            'suggestions' => [],
            'checks' => $checks
        ]);
    }
}
