<?php

namespace App\Events\V2\AvailabilityZone;

use App\Models\V2\AvailabilityZone;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\SerializesModels;

class Deleted
{
    use SerializesModels;

    public $availabilityZoneId;

    /**
     * @param Model $model
     * @return void
     */
    public function __construct(AvailabilityZone $model)
    {
        $this->availabilityZoneId = $model->getKey();
    }
}
