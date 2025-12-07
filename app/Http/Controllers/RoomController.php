<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Room;
use Illuminate\Support\Facades\Validator;
use App\Models\RoomType;
use Carbon\Carbon;


class RoomController extends Controller
{


    public function getAvailableRooms(Request $request)
    {
        try {
            $request->validate([
                'check_in_date' => 'nullable|date',
                'check_out_date' => 'nullable|date|after:check_in_date',
                'num_people' => 'nullable|integer|min:1',
                'room_type' => 'nullable|string',
                'max_price' => 'nullable|numeric|min:0'
            ]);

            $query = Room::with('roomType')
                ->where('statut', 'Libre');

            // Filtre par capacité
            if ($request->has('num_people') && $request->num_people) {
                $query->whereHas('roomType', function ($q) use ($request) {
                    $q->where('capacite', '>=', $request->num_people);
                });
            }

            // Filtre par type de chambre
            if ($request->has('room_type') && $request->room_type) {
                $query->whereHas('roomType', function ($q) use ($request) {
                    $q->where('nom', $request->room_type);
                });
            }

            // Filtre par prix maximum
            if ($request->has('max_price') && $request->max_price) {
                $query->whereHas('roomType', function ($q) use ($request) {
                    $q->where('base_prix', '<=', $request->max_price);
                });
            }

            // Filtre par disponibilité des dates
            if ($request->has('check_in_date') && $request->has('check_out_date')) {
                $checkIn = Carbon::parse($request->check_in_date);
                $checkOut = Carbon::parse($request->check_out_date);

                $query->whereDoesntHave('reservations', function ($q) use ($checkIn, $checkOut) {
                    $q->whereIn('statut', ['Confirmée', 'En Attente'])
                        ->where(function ($query) use ($checkIn, $checkOut) {
                            $query->whereBetween('date_debut', [$checkIn, $checkOut])
                                ->orWhereBetween('date_fin', [$checkIn, $checkOut])
                                ->orWhere(function ($q) use ($checkIn, $checkOut) {
                                    $q->where('date_debut', '<=', $checkIn)
                                        ->where('date_fin', '>=', $checkOut);
                                });
                        });
                });
            }

            $rooms = $query->get();

            $transformedRooms = $rooms->map(function ($room) {
                return [
                    'id' => $room->id,
                    'type' => $room->roomType->nom,
                    'statut' => $room->statut,
                    'image' => $room->photo ? asset('storage/' . $room->photo) : '/placeholder.svg',
                    'numero' => $room->numero,
                    'nom' => $room->roomType->nom,
                    'base_prix' => $room->roomType->base_prix,
                    'description' => $room->roomType->description,
                    'capacite' => $room->roomType->capacite,
                    'etage' => $room->etage,
                    'room_type_id' => $room->room_type_id
                ];
            });

            return response()->json([
                'success' => true,
                'rooms' => $transformedRooms,
                'total' => $transformedRooms->count()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Erreur lors de la recherche des chambres',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getRoomTypes()
    {
        try {
            $roomTypes = RoomType::select('nom', 'base_prix', 'capacite')->get();

            return response()->json([
                'success' => true,
                'room_types' => $roomTypes
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Erreur lors du chargement des types de chambres'
            ], 500);
        }
    }

    public function checkRoomAvailability(Request $request, $roomId)
    {
        try {
            $request->validate([
                'check_in_date' => 'required|date',
                'check_out_date' => 'required|date|after:check_in_date'
            ]);

            $room = Room::findOrFail($roomId);
            $checkIn = Carbon::parse($request->check_in_date);
            $checkOut = Carbon::parse($request->check_out_date);

            $isAvailable = !$room->reservations()
                ->whereIn('statut', ['Confirmée', 'En Attente'])
                ->where(function ($query) use ($checkIn, $checkOut) {
                    $query->whereBetween('date_debut', [$checkIn, $checkOut])
                        ->orWhereBetween('date_fin', [$checkIn, $checkOut])
                        ->orWhere(function ($q) use ($checkIn, $checkOut) {
                            $q->where('date_debut', '<=', $checkIn)
                                ->where('date_fin', '>=', $checkOut);
                        });
                })
                ->exists();

            return response()->json([
                'success' => true,
                'available' => $isAvailable,
                'room' => [
                    'id' => $room->id,
                    'type' => $room->roomType->nom,
                    'price' => $room->roomType->base_prix,
                    'capacity' => $room->roomType->capacite
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Erreur lors de la vérification de disponibilité'
            ], 500);
        }
    }
    public function show($id)
    {
        $room = Room::findOrFail($id); // récupère la chambre depuis DB
        return response()->json($room);
    }
    public function index()
    {
        $rooms = Room::all();


        $transformedRooms = $rooms->map(function ($room) {
            return [
                'id' => $room->id,
                'type' => $room->roomType->nom,
                'statut' => $room->statut,
                'image' => $room->photo,
                'numero' => $room->numero,
                'etage' => $room->etage,
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
