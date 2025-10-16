<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

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

    return response()->json($user);
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
