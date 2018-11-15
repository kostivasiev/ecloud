<?php

namespace App\Models\V1;

use Illuminate\Database\Eloquent\Model;

class UCSDatacentre extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ucs_datacentre';

    /**
     * The primary key associated with the model.
     *
     * @var string
     */
    protected $primaryKey = 'ucs_datacentre_id';

    /**
     * Indicates if the model should be timestamped
     *
     * @var bool
     */
    public $timestamps = false;
}
