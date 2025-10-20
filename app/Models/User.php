<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;


class User extends Authenticatable
{
    

     use HasApiTokens, Notifiable; // <-- Ajouter HasApiTokens ici

    protected $fillable = [
        'name',
        'email',
        'password',
    ];
    

    protected $hidden = [
        'password',
        'remember_token',
    ];


    

}
