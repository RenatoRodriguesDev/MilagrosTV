<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class AuthController extends Controller
{
    public function showLogin()
    {
        return redirect()->route('login');
    }

    public function login()
    {
        return redirect()->route('login');
    }

    public function logout()
    {
        return redirect()->route('login');
    }
}
