<?php

namespace App\Support;

use App\Models\User;
use BadMethodCallException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthSessionHash
{
    public static function sync(Request $request, User $user): void
    {
        $passwordHash = $user->getAuthPassword();
        $guard = Auth::guard('web');

        try {
            if (method_exists($guard, 'hashPasswordForCookie')) {
                $passwordHash = $guard->hashPasswordForCookie($passwordHash);
            }
        } catch (BadMethodCallException) {
            // Older guards store the raw password hash in the session.
        }

        $request->session()->put('password_hash_'.Auth::getDefaultDriver(), $passwordHash);
    }
}
