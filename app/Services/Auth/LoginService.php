<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Handles admin authentication attempts with lockout protection.
 */
class LoginService
{
    /**
     * Attempt login using email, username, or mobile.
     *
     * @param  array{login: string, password: string, remember?: bool}  $credentials
     *
     * @throws ValidationException
     */
    public function attempt(array $credentials, string $ip): User
    {
        $key = $this->throttleKey($credentials['login'], $ip);

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            Log::warning('Admin login lockout', ['login' => $credentials['login'], 'ip' => $ip]);

            throw ValidationException::withMessages([
                'login' => "Too many failed attempts. Try again in {$seconds} seconds.",
            ]);
        }

        $login = trim($credentials['login']);
        $user = User::query()
            ->where(function ($q) use ($login): void {
                $q->where('email', strtolower($login))
                    ->orWhere('username', strtolower($login))
                    ->orWhere('mobile', preg_replace('/[\s\-]/', '', $login));
            })
            ->first();

        if ($user === null || ! Hash::check($credentials['password'], $user->password)) {
            RateLimiter::hit($key, 600);
            Log::info('Admin login failed', ['login' => $login, 'ip' => $ip]);

            throw ValidationException::withMessages([
                'login' => 'These credentials do not match our records.',
            ]);
        }

        if (! $user->canAuthenticate()) {
            throw ValidationException::withMessages([
                'login' => 'This account is inactive or outside its valid access window.',
            ]);
        }

        Auth::login($user, (bool) ($credentials['remember'] ?? false));
        request()->session()->regenerate();
        RateLimiter::clear($key);

        $user->forceFill(['last_login_at' => now()])->save();
        Log::info('Admin login success', ['user_id' => $user->id, 'ip' => $ip]);

        return $user;
    }

    /**
     * Log the current user out and invalidate the session.
     */
    public function logout(): void
    {
        $userId = Auth::id();
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
        Log::info('Admin logout', ['user_id' => $userId]);
    }

    /**
     * Build a rate-limiter key for login + IP.
     */
    protected function throttleKey(string $login, string $ip): string
    {
        return Str::lower($login).'|'.$ip;
    }
}
