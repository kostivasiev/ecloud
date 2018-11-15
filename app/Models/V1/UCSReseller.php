<?php

namespace App\Models\V1;

use Illuminate\Database\Eloquent\Model;

class UCSReseller extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ucs_reseller';

    /**
     * The primary key associated with the model.
     *
     * @var string
     */
    protected $primaryKey = 'ucs_reseller_id';

    /**
     * Indicates if the model should be timestamped
     *
     * @var bool
     */
    public $timestamps = false;


    /**
     * Maps a UCS Reseller to a UCS Datacentre
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function UCSDatacentre()
    {
        return $this->hasOne(
            'App\Models\V1\UCSDatacentre',
            'ucs_datacentre_id',
            'ucs_reseller_datacentre_id'
        );
    }
}
