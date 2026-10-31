<?php

namespace App\Controllers;

class Home extends BaseController
{
    public function index(): string
    {
        return view('welcome_message');
    }

    public function dashboard()
    {
        if (! session('api_token')) {
            return redirect()->to('/');
        }

        return view('dashboard', ['user' => session('api_user') ?? [], 'apiToken' => session('api_token') ?? '']);
    }
}
