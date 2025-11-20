<?php

namespace App\Http\Controllers;
use App\Models\Chambre;

use Illuminate\Http\Request;

class ChambreController extends Controller
{
     public function store(Request $request)
    {
        $request->validate([
            'numero' => 'required|unique:chambres,numero',
            'type' => 'required|string',
            'capacite' => 'required|integer',
            'prix' => 'required|numeric',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $chambre = new Chambre();
        $chambre->numero = $request->numero;
        $chambre->type = $request->type;
        $chambre->capacite = $request->capacite;
        $chambre->prix = $request->prix;

        // upload de la photo
        if ($request->hasFile('photo')) {
            $file = $request->file('photo');
            $path = $file->store('chambres', 'public'); // storage/app/public/chambres
            $chambre->photo = $path;
        }

        $chambre->save();

        return response()->json([
            'message' => 'Chambre ajoutée avec succès',
            'chambre' => $chambre
        ]);
    }
}
