<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\VerifyEmailResponse as Contract;

class VerifyEmailResponse implements Contract
{
    public function toResponse($request)
    {
        return redirect('/attendance');
    }
}