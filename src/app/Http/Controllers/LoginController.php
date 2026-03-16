<?php

namespace App\Http\Controllers;

class LoginController extends Controller
{
    /**
     * ログインページ表示
     */
    public function index()
    {
        return view('login');
    }
}