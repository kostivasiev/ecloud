<?php

namespace App\Http\Middleware;

use Illuminate\Support\Facades\Auth;

trait ResellerBypass
{
    public function resellerBypass() : bool
    {
        return in_array(Auth::user()->resellerId(), config('reseller.internal'));
    }
}
