<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\VerificationController;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;;

Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail']);
Route::post('/reset-password', [NewPasswordController::class, 'reset']);
Route::get('/forgot-password/{token}', function ($token) {
    return redirect("http://localhost:5173/forgot-password/$token");
})->name('password.reset');
Route::post('/send-verification-code', [VerificationController::class, 'sendCode']);
Route::post('/verify-code', [VerificationController::class, 'verifyCode']);




// --- Register ---
Route::post('/register', function (Request $request) {
    $request->validate([
        'name' => 'required',
        'email' => 'required|email|unique:users',
        'password' => 'required|min:6'
    ]);

    $user = User::create([
        'name' => $request->name,
        'email' => $request->email,
        'password' => Hash::make($request->password)
    ]);

    // --- Générer code et token ---
    $code = rand(100000, 999999);
    $token = Str::random(60);

    $user->verification_code = $code;
    $user->verification_token = $token;
    $user->save();

    // --- Envoyer l'email ---
    Mail::raw("Your verification code is: $code", function ($message) use ($user) {
        $message->to($user->email)
                ->subject('Verify your email address');
    });

    // --- Retourner le token ---
    return response()->json([
        'message' => 'Inscription réussie ! Please check your email for the verification code.',
        'verification_token' => $token
    ]);
});


// --- Login ---


Route::post('/login', function (Request $request) {
    // Validation simple côté backend
    $request->validate([
        'email' => 'required|email',
        'password' => 'required',
    ]);

    // Chercher l'utilisateur par email
    $user = User::where('email', $request->email)->first();

    // Vérifier si utilisateur existe et si le mot de passe est correct
    if (! $user || ! Hash::check($request->password, $user->password)) {
        throw ValidationException::withMessages([
            'email' => ['Les informations sont incorrectes.'],
        ]);
    }

    // Générer un token avec Sanctum
    $token = $user->createToken('auth_token')->plainTextToken;

    // Retourner les infos utilisateur et le token
    return response()->json([
        'message' => 'Connexion réussie !',
        'user' => $user,
        'token' => $token,
    ]);
});


// --- Route protégée ---
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
