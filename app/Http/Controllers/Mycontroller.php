<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class Mycontroller extends Controller
{
    
    public function index()
    {
       $profiles = User::all();
       $profiles = User::paginate(10);
        return view(
            'welcome',compact('profiles')

        );
    }

    public function show(Request $request)
    {
        $id=(int)$request->id;
        $profile = User::find($id);
        return view(
            'show',compact('profile')
        );


    }
    public function formulaire()
    {
        return view('create');
    }

    public function store(Request $request)
    {
           
       $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6'
            
        ]);
        


        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password)
    
        ]);

      
            return redirect('/')->with('success', 'User created successfully.');
         

    }

}
