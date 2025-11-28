<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Room; // ✅ IMPORT MANQUANT

class Reservation extends Model
{
    protected $fillable = [
    'client_id',
    'room_id',
    'date_debut',
    'date_fin',
    'nbr_personnes',
    'total_prix',
    'statut',
    'date_reservation'
];

    protected $casts = [
        'services' => 'array',
        'check_in' => 'date',
        'check_out' => 'date'
    ];

    public function room()
    {
        return $this->belongsTo(Room::class);
    }
}
