<?php

namespace App\Models\V2;

use App\Traits\V2\ColumnPrefixHelper;
use App\Traits\V2\UUIDHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApplianceVersion extends Model
{
    use ColumnPrefixHelper, UUIDHelper, SoftDeletes;

    protected $connection = 'ecloud';
    protected $table = 'appliance_version';
    protected $primaryKey = 'appliance_version_uuid';
    public $incrementing = false;
    public $timestamps = true;

    protected $appends = [
        'appliance_uuid'
    ];

    /**
     * appliance_uuid is our non-database foreign key appliance_id as the appliance UUID
     * Queries for the appliance_version_appliance_id return the record's UUID, not the
     * internal ID stored in the column
     * @return mixed
     * @see also setApplianceVersionApplianceUuidAttribute($value)
     */
    public function getApplianceUuidAttribute()
    {
        $appliance =  Appliance::select('appliance_uuid')
            ->where('appliance_id', '=', $this->attributes['appliance_version_appliance_id']);

        if ($appliance->count() > 0) {
            return $appliance->first()->uuid;
        }

        // Appliance with that id was not found
        return null;
    }

    /**
     * Relation mapping: applianceVersion to appliance resource
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function appliance()
    {
        return $this->hasOne(
            'App\Models\V2\Appliance',
            'appliance_id',
            'appliance_version_appliance_id'
        );
    }
}
