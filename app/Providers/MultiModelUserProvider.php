<?php

namespace App\Providers;

use App\Models\AdminHelpdesk;
use App\Models\AdminAplikasi;
use App\Models\Teknisi;
use App\Models\User;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;

class MultiModelUserProvider extends EloquentUserProvider
{
    /**
     * The model priority order for authentication
     */
    protected $modelPriority = [
        AdminHelpdesk::class,
        AdminAplikasi::class,
        Teknisi::class,
        User::class,
    ];

    /**
     * Retrieve a user by their unique identifier.
     *
     * @param  mixed  $identifier
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveById($identifier)
    {
        // Try each model in priority order
        foreach ($this->modelPriority as $model) {
            try {
                // First try by primary key (id)
                $user = $model::find($identifier);
                if ($user) {
                    return $user;
                }
            } catch (\Exception $e) {
                // Continue to next model if this fails
            }
        }

        // If not found by ID, try by NIP as fallback
        foreach ($this->modelPriority as $model) {
            try {
                $user = $model::where('nip', $identifier)->first();
                if ($user) {
                    return $user;
                }
            } catch (\Exception $e) {
                // Continue to next model if this fails
            }
        }

        return null;
    }

    /**
     * Retrieve a user by their unique identifier and "remember me" token.
     *
     * @param  mixed  $identifier
     * @param  string  $token
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveByToken($identifier, $token)
    {
        // Try each model in priority order
        foreach ($this->modelPriority as $model) {
            $user = $model::where('nip', $identifier)->where('remember_token', $token)->first();
            if ($user) {
                return $user;
            }
        }

        return null;
    }

    /**
     * Update the "remember me" token for the given user in storage.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  string  $token
     * @return void
     */
    public function updateRememberToken(Authenticatable $user, $token)
    {
        // Set the remember token on the user
        $user->setRememberToken($token);
        
        // Only call save() if the method exists (for Eloquent models)
        // Using reflection API to avoid static type checking issues
        if (method_exists($user, 'save')) {
            $reflection = new \ReflectionClass($user);
            $saveMethod = $reflection->getMethod('save');
            $saveMethod->invoke($user);
        }
    }

    /**
     * Retrieve a user by the given credentials.
     *
     * @param  array  $credentials
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveByCredentials(array $credentials)
    {
        // Handle NIP-based authentication
        if (isset($credentials['nip'])) {
            foreach ($this->modelPriority as $model) {
                $user = $model::where('nip', $credentials['nip'])->first();
                if ($user) {
                    return $user;
                }
            }
        }

        // Fallback to email-based authentication for compatibility
        if (isset($credentials['email'])) {
            foreach ($this->modelPriority as $model) {
                $user = $model::where('email', $credentials['email'])->first();
                if ($user) {
                    return $user;
                }
            }
        }

        return null;
    }

    /**
     * Validate a user against the given credentials.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  array  $credentials
     * @return bool
     */
    public function validateCredentials(Authenticatable $user, array $credentials)
    {
        $plain = $credentials['password'];

        return $this->hasher->check($plain, $user->getAuthPassword());
    }
}