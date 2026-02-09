<?php

namespace App\Http\Controllers;

class RegisterController extends Controller
{
    /**
     * ユーザ登録ページ表示
     */
    public function index()
    {
        return view('register');
    }
}
