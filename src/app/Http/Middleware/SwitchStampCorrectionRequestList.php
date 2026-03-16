<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\AdminChangeRequestController;
use App\Http\Controllers\AttendanceChangeRequestController;
use Illuminate\Routing\Router;

class SwitchStampCorrectionRequestList
{
    public function handle(Request $request, Closure $next)
    {
        $result = null;

        if (Auth::guard('admin')->check()) {
            $controller = app(AdminChangeRequestController::class);
            $result = $controller->index($request);
        } elseif (Auth::guard('web')->check()) {
            $controller = app(AttendanceChangeRequestController::class);
            $result = $controller->index($request);
        } else {
            return redirect()->route('login');
        }

        return app(Router::class)->toResponse($request, $result);
    }
}
