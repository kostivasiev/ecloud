<?php
namespace App\Models\V2;

use Illuminate\Database\Eloquent\Relations\Pivot;

class IpAddressNic extends Pivot
{
    protected $connection = 'ecloud';
}
