<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    protected $fillable = [
        'id',
    'hotel_id',
    'room_type_id',
    'numero',
    'statut',
    'etage',
    'photo'
];
    

    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }
     public function roomType()
     {
        
        return $this->belongsTo(RoomType::class);
     }
     
    

}