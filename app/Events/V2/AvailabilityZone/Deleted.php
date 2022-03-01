<?php

namespace App\Events\V2\AvailabilityZone;

use App\Models\V2\AvailabilityZone;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\SerializesModels;

class Deleted
{
    use SerializesModels;

    public $model;

    /**
     * @param AvailabilityZone $model
     * @return void
     */
    public function __construct(AvailabilityZone $model)
    {
        $this->model = $model;
    }
}
