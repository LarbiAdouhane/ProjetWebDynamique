<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class VerificationController extends Controller
{
    // Envoyer le code après inscription
    public function sendCode(User $user)
    {
        // Générer un code aléatoire à 6 chiffres
        $code = rand(100000, 999999);
        $token = Str::random(60); // token unique

        $user->verification_code = $code;
        $user->verification_token = $token;
        $user->save();

        // Envoyer l'email
        Mail::raw("Your verification code is: $code", function ($message) use ($user) {
            $message->to($user->email)
                    ->subject('Verify your email address');
        });

        // Retourner le token pour le frontend
        return response()->json([
            'message' => 'Verification code sent successfully.',
            'verification_token' => $token
        ]);
    }

    // Vérifier le code
    public function verifyCode(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'code' => 'required'
        ]);

        $user = User::where('verification_token', $request->token)
                    ->where('verification_code', $request->code)
                    ->first();

        if (!$user) {
            return response()->json(['message' => 'Invalid verification code.'], 400);
        }

        $user->email_verified_at = now();
        $user->verification_code = null;
        $user->verification_token = null; // supprimer le token
        $user->save();

        return response()->json(['message' => 'Email verified successfully.']);
    }
}
