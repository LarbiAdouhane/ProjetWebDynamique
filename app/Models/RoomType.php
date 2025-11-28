<?php
// App/Models/RoomType.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoomType extends Model
{
    use HasFactory;

    protected $fillable = [
    'nom',
    'base_prix',
    'capacite',
    'description'
];

    // Relation avec Room
    
    public function rooms()
{
    return $this->hasMany(Room::class);
}
}