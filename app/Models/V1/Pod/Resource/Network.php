<?php

namespace App\Models\V1\Pod\Resource;

use App\Models\V1\Pod\ResourceAbstract;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Network extends ResourceAbstract
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'pod_resource_network';

    /**
     * @var array
     */
    protected $fillable = [
        // TODO
    ];
}
