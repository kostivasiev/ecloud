<?php

namespace App\Models\V1\Appliance\Version;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Data extends Model
{
    use SoftDeletes, HasFactory;

    /**
     * The connection name for the model.
     *
     * @var string
     */
    protected $connection = 'ecloud';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'appliance_version_data';

    /**
     * @var array
     */
    protected $fillable = [
        'key',
        'value',
        'appliance_version_uuid',
    ];
}
