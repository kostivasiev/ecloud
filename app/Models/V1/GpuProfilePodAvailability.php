<?php

namespace App\Models\V1;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GpuProfilePodAvailability extends Model
{
    use HasFactory;

    protected $connection = 'ecloud';

    protected $table = 'gpu_profile_pod_availability';

    protected $primaryKey = 'id';

    public $timestamps = false;
}
