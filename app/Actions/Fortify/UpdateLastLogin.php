<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Contracts\LoginResponse;

class UpdateLastLogin implements LoginResponse
{
    public function toResponse($request)
    {
        $user = Auth::user();

        if ($user instanceof User) {
            $user->update(['last_login_at' => now()]);
        }

        return redirect()->intended('/home');
    }
}
