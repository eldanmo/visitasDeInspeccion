<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class GoogleController extends Controller
{
    public function redirectToGoogle()
    {
        return Socialite::driver('google')
            ->scopes(['https://www.googleapis.com/auth/drive.file'])
            ->with(['access_type' => 'offline', 'prompt' => 'consent'])
            ->redirect();
    }

    public function handleGoogleCallback()
    {
        $user_google = Socialite::driver('google')->stateless()->user();

        /*$emailDomain = explode('@', $user_google->getEmail())[1];
        if ($emailDomain !== 'supersolidaria.gov.co') {
            return redirect('/')->with('error', 'El dominio de correo electrónico no es válido.');
        }*/

        $user = User::updateOrCreate([
            'google_id' => $user_google->getId(),
        ],[
            'name' => $user_google->getName(),
            'email' => $user_google->getEmail(),
            'google_token' => $user_google->token,
            'google_refresh_token' => $user_google->refreshToken,
        ]);

        session(['google_token' => $user_google->token]);
        session(['google_refresh_token' => $user_google->refreshToken]);

        Auth::login($user);

        return redirect('/dashboard');
    }
}
