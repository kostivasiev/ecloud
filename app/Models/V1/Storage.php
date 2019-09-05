<?php

namespace App\Models\V1;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Storage
 * Model for Storage table and accessing SAN credentials
 * @package App\Models\V1
 */
class Storage extends Model
{
    protected $table = 'ucs_storage';

    protected $primaryKey = 'id';

    public $timestamps = false;

    /**
     * Return the storage API Pod
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function pod()
    {
        return $this->hasOne(
            'App\Models\V1\Pod',
            'ucs_datacentre_id',
            'ucs_datacentre_id'
        );
    }

    /**
     * Return the SAN
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function san()
    {
        return $this->hasOne(
            'App\Models\V1\San',
            'servers_id',
            'server_id'
        );
    }

    /**
     * Return whether IOPS is configurable for the SAN
     * @return bool
     */
    public function qosEnabled()
    {
        return ($this->attributes['qos_enabled'] == 'Yes');
    }

    /**
     * Scope a query to only include solutions for a given reseller
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $podId
     * @return \Illuminate\Database\Eloquent\Builder $query
     */
    public function scopeWithPod($query, $podId)
    {
        $podId = filter_var($podId, FILTER_SANITIZE_NUMBER_INT);

        if (!empty($podId)) {
            $query->where('ucs_datacentre_id', $podId);
        }

        return $query;
    }
}
