<?php

namespace App\Models\V1;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Storage
 * Model for Storage table and accessing SAN credentials
 * @package App\Models\V1
 */
class Storage extends Model
{
    use HasFactory;

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
     * Scope a query to only include storage records for a given Pod
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param Pod $pod
     * @return \Illuminate\Database\Eloquent\Builder $query
     */
    public function scopeWithPod($query, Pod $pod)
    {
        $query->where('ucs_datacentre_id', '=', $pod->getKey());
        return $query;
    }
}
