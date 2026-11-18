<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\SuperAdmin;

class AdminAuthController extends Controller
{
    public function showLogin(){
        return view('admin.login');
    }

    public function login(Request $request){
        $credentials = $request->validate([
            'email'=>'required|email',
            'password'=>'required',
        ]);

        if(Auth::attempt($credentials)){
            $request->session()->regenerate();
            return redirect()->intended(route('admin.index'))->with('success','welcome to my page');
        }

        return back()->withErrors([
        'email' => 'email is not correct',
        ])->onlyInput('email');

    }



}