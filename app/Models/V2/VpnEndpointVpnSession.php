<?php
namespace App\Models\V2;

use Illuminate\Database\Eloquent\Relations\Pivot;

class VpnEndpointVpnSession extends Pivot
{
    protected $connection = 'ecloud';
}
