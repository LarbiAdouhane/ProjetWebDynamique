<?php

namespace App\Http\Controllers;
use App\Models\User;
use Illuminate\Http\Request;


class UserController extends Controller
{
    public function index()
    {
        return response()->json(User::all());
    }
    
    public function delete($id)
    {
        $user = User::find($id);
        if ($user) {
            $user->delete();
            return response()->json(['message' => 'Utilisateur supprimé avec succès.'], 200);
        }
        return response()->json(['message' => 'Utilisateur non trouvé.'], 404);
    }
}
