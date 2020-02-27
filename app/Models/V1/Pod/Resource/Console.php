<?php
namespace App\Models\V1\Pod\Resource;

use App\Models\V1\Pod\ResourceAbstract;

class Console extends ResourceAbstract
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'pod_resource_console';

    /**
     * @var array
     */
    protected $fillable = [
        'token',
        'url',
        'console_url',
    ];
}
