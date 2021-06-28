<?php
namespace App\Models\V2;

use Illuminate\Database\Eloquent\Relations\Pivot;

class VpnServiceVpnSession extends Pivot
{
    protected $connection = 'ecloud';
}
