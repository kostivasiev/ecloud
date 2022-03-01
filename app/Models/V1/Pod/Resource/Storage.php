<?php

namespace App\Models\V1\Pod\Resource;

use App\Models\V1\Pod\ResourceAbstract;

class Storage extends ResourceAbstract
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'pod_resource_storage';

    /**
     * @var array
     */
    protected $fillable = [
        // TODO
    ];
}
