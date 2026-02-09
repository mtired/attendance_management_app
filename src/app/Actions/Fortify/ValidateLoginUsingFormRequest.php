<?php

namespace App\Actions\Fortify;

use App\Http\Requests\LoginRequest;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/*** 自作バリデーション処理を認証前に行うために追加 ***/
class ValidateLoginUsingFormRequest
{
    public function __invoke(Request $request, Closure $next)
    {
        $form = app(LoginRequest::class);

        Validator::make(
            $request->all(),
            $form->rules(),
        )->validate();

        return $next($request);
    }
}