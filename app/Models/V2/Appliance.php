<?php

namespace App\Models\V2;

use App\Exceptions\V1\ApplianceVersionNotFoundException;
use App\Traits\V2\ColumnPrefixHelper;
use App\Traits\V2\UUIDHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Appliance extends Model
{
    use ColumnPrefixHelper, UUIDHelper, SoftDeletes;

    protected $connection = 'ecloud';
    protected $table = 'appliance';
    protected $primaryKey = 'appliance_uuid';
    public $incrementing = false;
    public $timestamps = true;

    const CREATED_AT = 'appliance_created_at';
    const UPDATED_AT = 'appliance_updated_at';
    const DELETED_AT = 'appliance_deleted_at';

    protected $appends = [
        'version'
    ];

    public function versions()
    {
        return $this->hasMany(
            'App\Models\V2\ApplianceVersion',
            'appliance_version_appliance_id',
            'appliance_id'
        );
    }

    /**
     * Get the latest version of the appliance.
     * @return Model|\Illuminate\Database\Eloquent\Relations\HasMany|object|null
     * @throws ApplianceVersionNotFoundException
     */
    public function getLatestVersion()
    {
        $version = $this->versions()->orderBy('appliance_version_version', 'DESC')->limit(1);
        if ($version->get()->count() > 0) {
            return $version->first();
        }

        throw new ApplianceVersionNotFoundException(
            'Unable to load latest version of the appliance. No versions were found.'
        );
    }

    /**
     * Get designation of the latest version of he application
     * @return string
     */
    public function getVersionAttribute()
    {
        try {
            $version = $this->getLatestVersion();
            return $version->version;
        } catch (ApplianceVersionNotFoundException $exception) {
            return 0;
        }
    }
}
