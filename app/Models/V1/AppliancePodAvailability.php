<?php

namespace App\Models\V1;

use App\Events\V1\AppliancePodAvailabilityDeletedEvent;
use App\Traits\V1\ColumnPrefixHelper;
use Illuminate\Database\Eloquent\Model;

class AppliancePodAvailability extends Model
{
    use ColumnPrefixHelper;

    protected $connection = 'ecloud';

    protected $table = 'appliance_pod_availability';

    protected $primaryKey = 'appliance_pod_availability_id';

    public $timestamps = false;

    // Events triggered by actions on the model
    protected $dispatchesEvents = [
        'deleting' => AppliancePodAvailabilityDeletedEvent::class
    ];
}
