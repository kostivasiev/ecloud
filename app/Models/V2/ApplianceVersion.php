<?php
/**
 * V1Refactor - Brought in from V1 namespace
 */
namespace App\Models\V2;

use App\Traits\V2\ColumnPrefixHelper;
use App\Traits\V2\UUIDHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;
use UKFast\Admin\Devices\AdminClient;

class ApplianceVersion extends Model
{
    use ColumnPrefixHelper, UUIDHelper, SoftDeletes;

    protected $connection = 'ecloud';
    protected $table = 'appliance_version';
    protected $primaryKey = 'appliance_version_uuid';
    public $incrementing = false;
    public $timestamps = true;

    const CREATED_AT = 'appliance_version_created_at';
    const UPDATED_AT = 'appliance_version_updated_at';
    const DELETED_AT = 'appliance_version_deleted_at';

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

    public function serverLicense()
    {
        $devicesAdminClient = app()->make(AdminClient::class);
        return $devicesAdminClient->licenses()->getById(
            $this->appliance_version_server_license_id
        );
    }

    public function getLatest(string $applianceUuid)
    {
        return $this->select('appliance_version_uuid')
            ->join('appliance', 'appliance.appliance_id', '=', 'appliance_version.appliance_version_appliance_id')
            ->where('appliance.appliance_uuid', '=', $applianceUuid)
            ->orderBy('appliance_version_version', 'desc')
            ->first()
            ->appliance_version_uuid;
    }
}
