<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class AdminProfileController extends Controller
{
    public function getProfile()
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Utilisateur non authentifié'
                ], 401);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'photo' => $user->photo ? asset('storage/' . $user->photo) : null,
                    'is_admin' => $user->is_admin
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du profil',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updateProfile(Request $request)
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Utilisateur non authentifié'
                ], 401);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|string|max:255',
                'email' => 'sometimes|email|max:255|unique:users,email,' . $user->id,
                'photo' => 'sometimes|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
                'remove_photo' => 'sometimes|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Données invalides',
                    'errors' => $validator->errors()
                ], 422);
            }

            $updateData = [];

            if ($request->has('name')) {
                $updateData['name'] = $request->name;
            }

            if ($request->has('email')) {
                $updateData['email'] = $request->email;
            }

            if ($request->hasFile('photo')) {
                $photoPath = $this->handlePhotoUpload($request->file('photo'), $user->photo);
                $updateData['photo'] = $photoPath;
            }

            if ($request->has('remove_photo') && $request->remove_photo === 'true') {
                $this->deleteExistingPhoto($user->photo);
                $updateData['photo'] = null;
            }

            if (!empty($updateData)) {
                User::where('id', $user->id)->update($updateData);
            }

            $updatedUser = User::find($user->id);

            return response()->json([
                'success' => true,
                'message' => 'Profil mis à jour avec succès',
                'data' => [
                    'name' => $updatedUser->name,
                    'email' => $updatedUser->email,
                    'photo' => $updatedUser->photo ? asset('storage/' . $updatedUser->photo) : null
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du profil',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updatePhoto(Request $request)
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Utilisateur non authentifié'
                ], 401);
            }

            $validator = Validator::make($request->all(), [
                'photo' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Photo invalide',
                    'errors' => $validator->errors()
                ], 422);
            }

            $photoPath = $this->handlePhotoUpload($request->file('photo'), $user->photo);
            
            User::where('id', $user->id)->update(['photo' => $photoPath]);
            
          
            $updatedUser = User::find($user->id);

            return response()->json([
                'success' => true,
                'message' => 'Photo mise à jour avec succès',
                'data' => [
                    'photo' => $updatedUser->photo ? asset('storage/' . $updatedUser->photo) : null
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour de la photo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function deletePhoto(Request $request)
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Utilisateur non authentifié'
                ], 401);
            }

            $this->deleteExistingPhoto($user->photo);
            
            User::where('id', $user->id)->update(['photo' => null]);
            
           
            $updatedUser = User::find($user->id);

            return response()->json([
                'success' => true,
                'message' => 'Photo supprimée avec succès',
                'data' => [
                    'photo' => null
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression de la photo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function handlePhotoUpload($photoFile, $existingPhotoPath = null)
    {
        if ($existingPhotoPath) {
            $this->deleteExistingPhoto($existingPhotoPath);
        }

        $fileName = 'profile_' . time() . '_' . uniqid() . '.' . $photoFile->getClientOriginalExtension();
        
        $path = $photoFile->storeAs('profiles', $fileName, 'public');
        
        return $path;
    }

    private function deleteExistingPhoto($photoPath)
    {
        if ($photoPath && Storage::disk('public')->exists($photoPath)) {
            Storage::disk('public')->delete($photoPath);
            return true;
        }
        return false;
    }
}