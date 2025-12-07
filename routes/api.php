<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\VerificationController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\ServiceController;

Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail']);
Route::post('/reset-password', [NewPasswordController::class, 'reset']);
Route::get('/forgot-password/{token}', function ($token) {
    return redirect("http://localhost:5173/forgot-password/$token");
})->name('password.reset');
Route::post('/send-verification-code', [VerificationController::class, 'sendCode']);
Route::post('/verify-code', [VerificationController::class, 'verifyCode']);
Route::get('/users', [UserController::class, 'index']);
Route::delete('/users/{id}', [UserController::class, 'delete']);

Route::get('/admin', function () {
    return User::where('role', 'admin')->first();
});

Route::get('/users', function () {
    return User::where('role', 'User')->first();
});







use App\Http\Controllers\RoomController;

Route::get('/rooms', [RoomController::class, 'index']);


Route::get('/rooms/available', [RoomController::class, 'getAvailableRooms']);
Route::get('/rooms/types', [RoomController::class, 'getRoomTypes']);
Route::get('/rooms/{roomId}/availability', [RoomController::class, 'checkRoomAvailability']);
Route::get('/rooms', [RoomController::class, 'index']);
Route::get('/rooms/{id}', [RoomController::class, 'show']);
Route::put('/rooms/{id}', [RoomController::class, 'update']);
Route::delete('/rooms/{id}', [RoomController::class, 'destroy']);

Route::post('/rooms', [RoomController::class, 'store']);
use App\Http\Controllers\RoomTypeController;

Route::get('/room-types', [RoomTypeController::class, 'index']);
Route::post('/reservations/create-temp', [ReservationController::class, 'createTemp']);


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

    $code = rand(100000, 999999);
    $token = Str::random(60);

    $user->verification_code = $code;
    $user->verification_token = $token;
    $user->save();

    Mail::raw("Your verification code is: $code", function ($message) use ($user) {
        $message->to($user->email)
            ->subject('Verify your email address');
    });

    return response()->json([
        'message' => 'Inscription réussie ! Please check your email for the verification code.',
        'verification_token' => $token
    ]);
});






Route::post('/login', function (Request $request) {
    $request->validate([
        'email' => 'required|email',
        'password' => 'required',
    ]);

    $user = User::where('email', $request->email)->first();

    if (! $user || ! Hash::check($request->password, $user->password)) {
        throw ValidationException::withMessages([
            'email' => ['Les informations sont incorrectes.'],
        ]);
    }

    $token = $user->createToken('auth_token')->plainTextToken;

    return response()->json([
        'message' => 'Connexion réussie !',
        'user' => $user,
        'token' => $token,
    ]);
});


Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

use App\Http\Controllers\PromoController;


Route::post('/validate-promo', [PromoController::class, 'validate']);

use App\Http\Controllers\Api\PayPalController;

Route::middleware(['api'])->group(function () {
    Route::post('/paypal/create-order', [PayPalController::class, 'createOrder']);
    Route::post('/paypal/capture-order', [PayPalController::class, 'captureOrder']);
});


Route::get('/payment/success', [PayPalController::class, 'success'])->name('payment.success');
Route::get('/payment/cancel', [PayPalController::class, 'cancel'])->name('payment.cancel');
// afficher réservations
Route::get('/reservations', [ReservationController::class, 'index']);

// créer réservation
Route::post('/reservations', [ReservationController::class, 'store']);

Route::get('/services', [ServiceController::class, 'index']);
