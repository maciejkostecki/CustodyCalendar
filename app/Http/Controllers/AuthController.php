<?php

namespace App\Http\Controllers;

use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Session;

class AuthController extends Controller
{
    private function allowedEmails(): array
    {
        return array_filter([
            env('ALLOWED_PARENT_1_EMAIL'),
            env('ALLOWED_PARENT_2_EMAIL'),
        ]);
    }

    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Exception $e) {
            return redirect(env('FRONTEND_URL') . '/login?error=oauth_failed');
        }

        if (!in_array($googleUser->getEmail(), $this->allowedEmails(), true)) {
            return redirect(env('FRONTEND_URL') . '/login?error=access_denied');
        }

        Session::put('user', [
            'email'  => $googleUser->getEmail(),
            'name'   => $googleUser->getName(),
            'avatar' => $googleUser->getAvatar(),
        ]);

        return redirect(env('FRONTEND_URL') . '/?auth=ok');
    }

    public function me()
    {
        if (!Session::has('user')) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }
        return response()->json(Session::get('user'));
    }

    public function logout()
    {
        Session::forget('user');
        return response()->json(['ok' => true]);
    }
}