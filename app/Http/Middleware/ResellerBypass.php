<?php

namespace App\Http\Middleware;

use Illuminate\Support\Facades\Auth;

trait ResellerBypass
{
    public array $resellerBypass = [
        7052, // UKFast - eCloud Testing
        22114, // UKFast - eCloud Automated Testing
    ];

    public function resellerBypass() : bool
    {
        return in_array(Auth::user()->resellerId(), $this->resellerBypass);
    }
}
