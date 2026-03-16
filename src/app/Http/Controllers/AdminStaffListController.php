<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class AdminStaffListController extends Controller
{
    public function index(Request $request)
    {
        $users = User::orderBy('id', 'asc')->get();

        return view('admin.staff_list', compact('users'));
    }
}
