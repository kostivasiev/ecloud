<?php
namespace App\Models\V1\Pod\Service;

use App\Models\V1\Pod\ServiceAbstract;

class Flint extends ServiceAbstract
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'pod_service_flint';

    /**
     * @var array
     */
    protected $fillable = [
        // TODO
    ];
}
