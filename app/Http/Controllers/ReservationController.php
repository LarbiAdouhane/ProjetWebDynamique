<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Reservation;
use App\Models\Room;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class ReservationController extends Controller
{
    public function index()
    {
        $reservations = Reservation::with('room.roomType')->get();

        $transformedReservations = $reservations->map(function ($reservation) {


            $dateDebut = Carbon::parse($reservation->date_debut);
            $dateFin = Carbon::parse($reservation->date_fin);


            $jours = $dateDebut->diffInDays($dateFin);

            $prixParNuit = $reservation->room->roomType->base_prix ?? 0;

            $total = $reservation->total_prix ?? ($prixParNuit * $jours);

            return [
                'id' => $reservation->id,

                'client_id' => $reservation->client_id,

                'room_id' => $reservation->room->id ?? null,
                'room_type' => $reservation->room->roomType->nom ?? null,

                'date_debut' => $reservation->date_debut,
                'date_fin' => $reservation->date_fin,
                'jours' => $jours,

                'base_prix' => $prixParNuit,
                'total_prix' => $total,

                'nbr_personnes' => $reservation->nbr_personnes,

                'statut' => $reservation->statut,
                'date_reservation' => $reservation->date_reservation
            ];
        });

        return response()->json($transformedReservations);
    }
    public function createTemp(Request $request)
    {
        $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'client_id' => 'nullable|exists:users,id',
            'date_debut' => 'required|date',
            'date_fin' => 'required|date|after_or_equal:date_debut',
            'nbr_personnes' => 'required|integer|min:1',
            'total_prix' => 'required|numeric|min:0',
            'statut' => 'required|string',
            'date_reservation' => 'required|date',
        ]);

        $reservation = Reservation::create($request->all());
        return response()->json([
            'success' => true,
            'id' => $reservation->id,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'date_debut' => 'required|date',
            'date_fin' => 'required|date|after:date_debut',
        ]);

        $room = Room::with('roomType')->findOrFail($request->room_id);

        $jours = Carbon::parse($request->date_debut)
            ->diffInDays(Carbon::parse($request->date_fin));

        $jours = max($jours, 1);

        $total = $room->roomType->base_prix * $jours;

        $reservation = Reservation::create([
            'client_id' => Auth::id(),
            'room_id' => $room->id,
            'date_debut' => $request->date_debut,
            'date_fin' => $request->date_fin,
            'nbr_personnes' => $room->roomType->capacite,
            'prix' => $room->roomType->base_prix,
            'statut' => 'En Attente',
            'date_reservation' => now(),
        ]);


        return response()->json([
            'message' => '✅ Réservation créée avec succès',
            'reservation' => $reservation
        ], 201);
    }
}
