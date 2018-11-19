<?php

namespace App\Models\V1;

use Illuminate\Database\Eloquent\Model;

class ServerLicenseAvailable extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'server_license_available';

    /**
     * The primary key associated with the model.
     *
     * @var string
     */
    protected $primaryKey = 'server_license_available_id';

    /**
     * Indicates if the model should be timestamped
     *
     * @var bool
     */
    public $timestamps = false;
}
