<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Exception;

class GoogleAuthController extends Controller
{
    /**
     * Redirection vers Google
     */
    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Callback après login Google
     */
    public function callback(Request $request)
    {
        try {
            // Récupérer l'utilisateur Google via Socialite
            $googleUser = Socialite::driver('google')->user();

            // Chercher l'utilisateur existant
            $user = User::where('google_id', $googleUser->id)
                        ->orWhere('email', $googleUser->email)
                        ->first();

            if ($user) {
                // Mettre à jour l'utilisateur existant
                $user->update([
                    'google_id' => $googleUser->id,
                    'avatar' => $googleUser->avatar,
                ]);
            } else {
                // Créer un nouvel utilisateur
                $user = User::create([
                    'name' => $googleUser->name,
                    'email' => $googleUser->email,
                    'google_id' => $googleUser->id,
                    'avatar' => $googleUser->avatar,
                    'password' => bcrypt(Str::random(16)),
                ]);
            }

            // Connecter l'utilisateur
            Auth::login($user);

            // Générer le token JWT (Sanctum)
            $token = $user->createToken('auth_token')->plainTextToken;

            // Rediriger vers le frontend avec le token
            return redirect("http://localhost:5173/auth/callback?token=$token");

        } catch (Exception $e) {
            return redirect('/')->with('error', 'Google authentication failed: ' . $e->getMessage());
        }
    }
}