<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Reservation;
use App\Models\Payment;
use Illuminate\Support\Facades\Log;


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

    public function success(Request $request)
    {
        $orderId = $request->query('token');
        $payerId = $request->query('PayerID');

        $paymentController = app(\App\Http\Controllers\Api\PayPalController::class);
        $response = $paymentController->captureOrder(new Request([
            'orderId' => $orderId,
            'reservation_id' => session('reservation_id')
        ]));

        return view('payment.success', [
            'orderId' => $orderId,
            'payerId' => $payerId,
            'payment_status' => $response->getData()->payment_status
        ]);
    }


    public function cancel()
    {
        return "Paiement annulé par l'utilisateur.";
    }

    public function createOrder(Request $request)
    {
        $request->validate([
            'reservation_id' => 'required|exists:reservations,id',
            'amount' => 'required|numeric|min:0.01'
        ]);

        $reservation = Reservation::findOrFail($request->reservation_id);
        $amount = number_format($request->amount, 2, '.', '');
        $currency = env('PAYPAL_CURRENCY', 'MAD');

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

                ]
            );

            if ($response->failed()) {
                return response()->json([
                    'error' => 'Erreur PayPal : création de commande échouée',
                    'details' => $response->body()
                ], 500);
            }

            $orderData = $response->json();

            Payment::create([
                'reservation_id' => $reservation->id,
                'montant' => $amount,
                'mode' => 'PayPal',
                'statut' => 'En Attente',
                'order_id' => $orderData['id'],
                'currency' => $currency
            ]);

            $approvalUrl = collect($orderData['links'])->firstWhere('rel', 'approve')['href'] ?? null;

            return response()->json([
                'order_id' => $orderData['id'],   // ✅ ESSENTIEL
                'approval_url' => $approvalUrl
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur interne du serveur',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function captureOrder(Request $request)
    {
        $request->validate([
            'reservation_id' => 'required|exists:reservations,id',
            'orderId' => 'required|string'
        ]);

        $reservation = Reservation::findOrFail($request->reservation_id);

        if ($request->query('test') === 'true') {

            $payment = Payment::where('reservation_id', $reservation->id)->first();
            if (!$payment) {
                return response()->json(['error' => 'Paiement introuvable'], 404);
            }
            $payment->update([
                'statut' => 'Payé',
                'date_paiement' => now()
            ]);
            $payment->save();
            $reservation->update([
                'payment_status' => 'Payé'
            ]);

            $reservation->save();


            return response()->json([
                'success' => true,
                'status' => 'COMPLETED',
                'fake' => true
            ]);
        }

        $orderId = $request->orderId;
        $token = $this->getAccessToken();
        $url = $this->baseUrl() . "/v2/checkout/orders/{$orderId}/capture";

        $response = \Illuminate\Support\Facades\Http::withHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ])
            ->withToken($token)
            ->send('POST', $url, [
                'body' => ''
            ]);
        $responseData = $response->json();

        // ⭐ AJOUT DE LA PARTIE CAPTURE RÉUSSIE
        if ($response->successful()) {
            $status = $responseData['status'] ?? 'UNKNOWN';
            $captureStatus = $responseData['purchase_units'][0]['payments']['captures'][0]['status'] ?? 'UNKNOWN';

            if ($status === 'COMPLETED' || $captureStatus === 'COMPLETED') {
                $payment = Payment::where('order_id', $orderId)->first();

                if ($payment) {
                    $payment->update([
                        'statut' => 'Payé',
                        'date_paiement' => now(),
                        'paypal_data' => json_encode($responseData)
                    ]);
                    $payment->save(); // ⭐ AJOUT ICI
                }

                $reservation->update([
                    'payment_status' => 'Payé'
                ]);
                $reservation->save(); // ⭐ AJOUT ICI

                return response()->json([
                    'success' => true,
                    'status' => 'COMPLETED',
                    'message' => 'Paiement capturé avec succès'
                ]);
            }
        }


        return response()->json([
            'URL' => $url,
            'ORDER_ID' => $orderId,
            'HTTP_CODE' => $response->status(),
            'PAYPAL_RESPONSE' => $response->json(),
            'RAW' => $response->body()
        ]);
    }
}
