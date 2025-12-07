<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Service;

class ServiceController extends Controller
{
     public function index()
    {
        $services = Service::all();
        $transformedServices = $services->map(function($service) {
            return [
                'id' => $service->id,
                'name' => $service->nom,        
                'price' => $service->prix,      
            ];
        });
        
        return response()->json($transformedServices);
}

}
