<?php

namespace App\Http\Controllers;
use App\Models\RoomType;

class RoomTypeController extends Controller
{
    public function index()
    {
        // Récupérer tous les types
        $roomTypes = RoomType::all();

        // Optionnel : transformer si besoin
        $transformed = $roomTypes->map(function($type) {
            return [
                'id' => $type->id,
                'nom' => $type->nom,
                'capacite' => $type->capacite,
                'base_prix' => $type->base_prix,
                'description' => $type->description,
            ];
        });

        return response()->json($transformed);
    }
}
