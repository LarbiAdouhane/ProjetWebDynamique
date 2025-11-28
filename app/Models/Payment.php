<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Payment extends Model
{
    use HasFactory;

  protected $fillable = [
        'reservation_id',
        'montant',
        'mode',
        'statut',
        'date_paiement',
        'order_id',
        'currency'
    ];
    // Relation : un paiement appartient à un utilisateur
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

