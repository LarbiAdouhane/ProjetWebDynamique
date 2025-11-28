<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Room;
use Illuminate\Support\Facades\Validator;
use App\Models\RoomType;

class RoomController extends Controller
{
    public function index()
    {
        $rooms = Room::all();

        
        $transformedRooms = $rooms->map(function($room) {
            return [
                'id' => $room->id,
                'type' => $room->roomType->nom,
                'statut' => $room->statut,
                'image' => '/placeholder.svg',
                'nom' => $room->roomType->nom,
                'base_prix' => $room->roomType->base_prix,
                'description' => $room->roomType->description,
                'capacite' => $room->roomType->capacite

            ];
        });
        
        return response()->json($transformedRooms);
    }

 public function store(Request $request)
{
    $request->validate([
        'numero' => 'required|unique:rooms,numero',
        'room_type_id' => 'required|exists:room_types,id',
        'statut' => 'required|in:Libre,Occupé,Maintenance',
        'etage' => 'nullable|string|max:50',
        'photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048'
    ]);

    $room = new Room();
    $room->numero = $request->numero;
    $room->room_type_id = $request->room_type_id;
    $room->statut = $request->statut;
    $room->etage = $request->etage ?? null;

    if ($request->hasFile('photo')) {
        $room->photo = $request->file('photo')->store('rooms', 'public');
    }

    $room->save();

    return response()->json([
        'success' => true,
        'message' => 'Chambre ajoutée avec succès',
        'room' => $room->load('roomType')
    ]);
}

public function update(Request $request, $id)
{
    $request->validate([
        'statut' => 'required|in:Libre,Occupé,Maintenance',
        'capacite' => 'required|integer',
        'base_prix' => 'required|numeric'
    ]);

    $room = Room::findOrFail($id);

    // Mettre à jour uniquement les champs modifiables
    $room->update($request->only(['statut', 'capacite', 'base_prix']));
    $room->roomType->update([
    'base_prix' => $request->base_prix,
    'capacite' => $request->capacite
]);

    return response()->json([
        'success' => true,
        'room' => [
            'id' => $room->id,
            'statut' => $room->statut,
            
                'base_prix' => $room->roomType->base_prix,
                'capacite' => $room->roomType->capacite,
            'room_type' => [
                'nom' => $room->roomType->nom,
                'description' => $room->roomType->description,
                'base_prix' => $room->roomType->base_prix,
                'capacite' => $room->roomType->capacite,
            ],
        ]
    ]);
}

    public function destroy($id)
    {
        try {
            $room = Room::find($id);
            
            if (!$room) {
                return response()->json([
                    'success' => false,
                    'message' => 'Chambre non trouvée'
                ], 404);
            }

            $room->delete();

            return response()->json([
                'success' => true,
                'message' => 'Chambre supprimée avec succès'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}