<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Reservation;
use App\Models\Payment;

class PayPalController extends Controller
{
    private function baseUrl()
    {
        return env('PAYPAL_BASE', 'https://api-m.sandbox.paypal.com');
    }

    private function getAccessToken()
    {
        $resp = Http::asForm()
            ->withBasicAuth(env('PAYPAL_CLIENT_ID'), env('PAYPAL_SECRET'))
            ->post($this->baseUrl() . '/v1/oauth2/token', [
                'grant_type' => 'client_credentials'
            ]);

        if ($resp->failed()) {
            abort(500, 'Erreur PayPal : Impossible d\'obtenir le token.');
        }

        return $resp->json()['access_token'];
    }

    /**
     * 1️⃣ Créer une commande PayPal
     */
    public function createOrder(Request $request)
    {
        // Validation des données
        $request->validate([
            'reservation_id' => 'required|exists:reservations,id',
            'amount' => 'required|numeric|min:0.01'
        ]);

        $reservation = Reservation::findOrFail($request->reservation_id);
        $amount = number_format($request->amount, 2, '.', '');
        $currency = env('PAYPAL_CURRENCY', 'EUR');

        try {
            $token = $this->getAccessToken();

            $response = Http::withToken($token)->post(
                $this->baseUrl() . '/v2/checkout/orders',
                [
                    "intent" => "CAPTURE",
                    "purchase_units" => [
                        [
                            "reference_id" => "reservation_" . $reservation->id,
                            "amount" => [
                                "currency_code" => $currency,
                                "value" => $amount
                            ]
                        ]
                    ],
                    "application_context" => [
                        "return_url" => url('/payment/success'),
                        "cancel_url" => url('/payment/cancel'),
                        "brand_name" => env('APP_NAME', 'Votre Application')
                    ]
                ]
            );

            if ($response->failed()) {
                return response()->json([
                    'error' => 'Erreur PayPal : Création de commande échouée.',
                    'details' => $response->body()
                ], 500);
            }

            $orderData = $response->json();

            // Créer un enregistrement de paiement en attente
            Payment::create([
                'reservation_id' => $reservation->id,
                'montant' => $amount,
                'mode' => 'PayPal',
                'statut' => 'En Attente',
                'order_id' => $orderData['id'],
                'currency' => $currency
            ]);

            return response()->json($orderData);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur interne du serveur',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 2️⃣ Capturer le paiement PayPal + Enregistrer en BD
     */
    public function captureOrder(Request $request)
    {
        // Validation des données
        $request->validate([
            'orderId' => 'required',
            'reservation_id' => 'required|exists:reservations,id'
        ]);

        try {
            $reservation = Reservation::findOrFail($request->reservation_id);
            $token = $this->getAccessToken();

            $response = Http::withToken($token)
                ->post($this->baseUrl() . "/v2/checkout/orders/{$request->orderId}/capture");

            if ($response->failed()) {
                // Marquer le paiement comme échoué
                Payment::where('order_id', $request->orderId)
                    ->where('reservation_id', $request->reservation_id)
                    ->update(['statut' => 'Échoué']);

                return response()->json([
                    'error' => 'Erreur PayPal : Capture échouée.',
                    'details' => $response->body()
                ], 500);
            }

            $data = $response->json();
            $status = $data['status'] ?? 'unknown';

            // Extraire montant capturé
            $amount = $data['purchase_units'][0]['payments']['captures'][0]['amount']['value'] ?? $reservation->total_price;

            // Mettre à jour le paiement
            $payment = Payment::where('order_id', $request->orderId)
                ->where('reservation_id', $request->reservation_id)
                ->first();

            if ($payment) {
                $payment->update([
                    'statut' => $status === 'COMPLETED' ? 'Payé' : 'Échoué',
                    'date_paiement' => $status === 'COMPLETED' ? now() : null,
                    'montant' => $amount
                ]);
            }

            // Mettre à jour la réservation si payée
            if ($status === 'COMPLETED') {
                $reservation->update([
                    'payment_status' => 'Payé',
                ]);
            }

            return response()->json([
                'success' => true,
                'status' => $status,
                'payment_status' => $status === 'COMPLETED' ? 'Payé' : 'Échoué'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur interne du serveur',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}