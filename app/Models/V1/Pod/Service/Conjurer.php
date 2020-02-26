<?php
namespace App\Models\V1\Pod\Service;

use App\Models\V1\Pod\ServiceAbstract;

class Conjurer extends ServiceAbstract
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'pod_service_conjurer';

    /**
     * @var array
     */
    protected $fillable = [
        // TODO
    ];
}
