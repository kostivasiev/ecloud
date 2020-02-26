<?php
namespace App\Models\V1\Pod\Service;

use App\Models\V1\Pod\ServiceAbstract;

class Envoy extends ServiceAbstract
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'pod_service_envoy';

    /**
     * @var array
     */
    protected $fillable = [
        'token',
        'url',
        'console_url',
    ];
}
