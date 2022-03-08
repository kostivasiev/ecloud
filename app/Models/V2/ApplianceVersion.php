<?php
/**
 * V1Refactor - Brought in from V1 namespace
 */

namespace App\Models\V2;

use App\Traits\V2\ColumnPrefixHelper;
use App\Traits\V2\UUIDHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;
use UKFast\Admin\Devices\AdminClient;
use UKFast\SDK\Exception\ServerException;

class ApplianceVersion extends V1ModelWrapper
{
    use HasFactory, ColumnPrefixHelper, UUIDHelper, SoftDeletes;

    protected $connection = 'ecloud';
    protected $table = 'appliance_version';
    protected $primaryKey = 'appliance_version_uuid';
    protected $keyType = 'string';
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
        $appliance = Appliance::select('appliance_uuid')
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

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function applianceScriptParameters()
    {
        return $this->hasMany(
            ApplianceScriptParameters::class,
            'appliance_script_parameters_appliance_version_id',
            'appliance_version_id'
        );
    }

    public function applianceVersionData()
    {
        return $this->hasMany(
            ApplianceVersionData::class,
            'appliance_version_uuid',
            'appliance_version_uuid'
        );
    }

    public function serverLicense()
    {
        $devicesAdminClient = app()->make(AdminClient::class);
        try {
            return $devicesAdminClient->licenses()->getById(
                $this->appliance_version_server_license_id
            );
        } catch (ServerException $exception) {
            Log::error($exception->getMessage(), ['response' => $exception->getResponse()]);
            throw $exception;
        }
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

    public function getScriptParameters(): array
    {
        $params = [];
        $parameters = $this->applianceScriptParameters()->get();
        foreach ($parameters as $parameter) {
            $params[$parameter->key] = $parameter;
        }

        return $params;
    }
}
