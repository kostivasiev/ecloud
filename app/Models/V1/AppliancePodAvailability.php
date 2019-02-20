<?php

namespace App\Models\V1;

use Illuminate\Database\Eloquent\Model;

use App\Traits\V1\ColumnPrefixHelper;

class AppliancePodAvailability extends Model
{
    use ColumnPrefixHelper;

    protected $connection = 'ecloud';

    protected $table = 'appliance_pod_availability';

    protected $primaryKey = 'appliance_pod_availability_id';

    public $timestamps = false;
}
