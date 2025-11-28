<?php

namespace App\Http\Controllers;
use App\Models\PromoCode;
use Illuminate\Http\Request;

class PromoController extends Controller
{

 public function validate(Request $request)
    {
        $code = $request->input('code');
        
        $promoCode = PromoCode::where('code', strtoupper($code))->first();
        
        if ($promoCode) {
            return response()->json([
                'valid' => true,
                'discount' => $promoCode->discount,
                'label' => $promoCode->label
            ]);
        }
        
        return response()->json([
            'valid' => false,
            'message' => 'Code promo invalide'
        ], 404);
    }

}
