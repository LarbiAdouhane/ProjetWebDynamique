<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;


class User extends Authenticatable
{
    

     use HasApiTokens, Notifiable; 

    protected $fillable = [
        'name',
        'email',
        'Role',
        'password',
        'avatar',
    'google_id',
    ];
    

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function reservations()
    {
        return $this->hasMany(Reservation::class, 'client_id');
    }

    

}
