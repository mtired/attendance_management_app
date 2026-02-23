<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\AdminLoginRequest;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController as FortifySession;

class AdminFortifySessionController extends Controller
{
    /**
     * ログインページ(管理者)表示
     */
    public function index()
    {
        return view('admin.login');
    }

    public function store(AdminLoginRequest $request, FortifySession $fortify)
    {
        config(['fortify.guard' => 'admin']);
        return $fortify->store($request);
    }

    public function destroy(Request $request, FortifySession $fortify)
    {
        config(['fortify.guard' => 'admin']);
        return $fortify->destroy($request);
    }
}
