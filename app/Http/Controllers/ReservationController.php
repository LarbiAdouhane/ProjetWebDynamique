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

    $transformedReservations = $reservations->map(function($reservation) {

        // Dates
        $dateDebut = Carbon::parse($reservation->date_debut);
        $dateFin = Carbon::parse($reservation->date_fin);

        // Durée
        $jours = $dateDebut->diffInDays($dateFin);

        // Prix par nuit
        $prixParNuit = $reservation->room->roomType->base_prix ?? 0;

        // ✅ Total calculé (ou stocké)
        $total = $reservation->total_prix ?? ($prixParNuit * $jours);

        return [
            'id' => $reservation->id,

            'client_id' => $reservation->client_id,

            // Chambre
            'room_id' => $reservation->room->id ?? null,
            'room_type' => $reservation->room->roomType->nom ?? null,

            // Dates
            'date_debut' => $reservation->date_debut,
            'date_fin' => $reservation->date_fin,
            'jours' => $jours,

            // Prix
            'base_prix' => $prixParNuit,
            'total_prix' => $total,

            // Personnes
            'nbr_personnes' => $reservation->nbr_personnes,

            // Statut
            'statut' => $reservation->statut,
            'date_reservation' => $reservation->date_reservation
        ];
    });

    return response()->json($transformedReservations);
}

    // ✅ CRÉER UNE RÉSERVATION
    public function store(Request $request)
    {
        // Validation
        $validated = $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'date_debut' => 'required|date',
            'date_fin' => 'required|date|after:date_debut',
        ]);

        // Charger la chambre avec son type
        $room = Room::with('roomType')->findOrFail($request->room_id);

        // Calcul nombre de jours
        $jours = Carbon::parse($request->date_debut)
                ->diffInDays(Carbon::parse($request->date_fin));

        // Si 0 jours → minimum 1
        $jours = max($jours, 1);

        // Calcul total
        $total = $room->roomType->base_prix * $jours;
        // Création réservation
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
