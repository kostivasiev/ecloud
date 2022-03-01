<?php

namespace App\Models\V1;

use Illuminate\Database\Eloquent\Model;

class Trigger extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'triggers';

    /**
     * The primary key associated with the model.
     *
     * @var string
     */
    protected $primaryKey = 'trigger_id';

    /**
     * Indicates if the model should be timestamped
     *
     * @var bool
     */
    public $timestamps = false;
}
