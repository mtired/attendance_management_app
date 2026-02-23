<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SetFortifyAdminGuard
{
    public function handle(Request $request, Closure $next)
    {
        // このリクエストでは Fortify が admin guard を使うように切り替える
        config(['fortify.guard' => 'admin']);

        return $next($request);
    }
}
