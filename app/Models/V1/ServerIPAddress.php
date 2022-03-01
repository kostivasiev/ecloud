<?php

namespace App\Models\V1;

use Illuminate\Database\Eloquent\Model;

class ServerIPAddress extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'server_ip_address';

    /**
     * The primary key associated with the model.
     *
     * @var string
     */
    protected $primaryKey = 'server_ip_address_id';

    /**
     * Indicates if the model should be timestamped
     *
     * @var bool
     */
    public $timestamps = false;
}
