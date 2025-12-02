<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Illuminate\Http\JsonResponse;
use App\Services\AuthService;

abstract class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    /**
     * Standard success JSON response
     */
    protected function successResponse($data = null, string $message = 'Success', int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], $status);
    }

    /**
     * Standard error JSON response
     */
    protected function errorResponse(string $message = 'Error', $errors = null, int $status = 400): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors
        ], $status);
    }

    /**
     * Standard Inertia response
     */
    protected function inertiaResponse(string $component, array $props = []): InertiaResponse
    {
        return Inertia::render($component, $props);
    }

    /**
     * Redirect back with success message
     */
    protected function redirectBack(string $message = 'Operation successful')
    {
        return back()->with('success', $message);
    }

    /**
     * Redirect back with error message
     */
    protected function redirectBackWithError(string $message = 'Operation failed')
    {
        return back()->withErrors(['error' => $message]);
    }

    /**
     * Get authenticated user with role information
     */
    protected function getAuthenticatedUser()
    {
        $user = Auth::user();
        if (!$user) {
            return null;
        }

        $authService = app(\App\Services\AuthService::class);
        $role = $authService->getUserRole($user);

        return (object) [
            'user' => $user,
            'role' => $role,
            'nip' => $user->nip,
            'name' => $user->name,
            'email' => $user->email ?? null,
        ];
    }
}
