<?php
/**
 * V1Refactor - Brought in from V1 namespace
 */

namespace App\Models\V2;

use App\Traits\V2\ColumnPrefixHelper;
use App\Traits\V2\UUIDHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use UKFast\Api\Exceptions\NotFoundException;

class ApplianceScriptParameters extends Model
{
    use HasFactory, ColumnPrefixHelper, UUIDHelper, SoftDeletes;

    protected $connection = 'ecloud';
    protected $table = 'appliance_script_parameters';
    protected $primaryKey = 'appliance_script_parameters_uuid';
    public $incrementing = false;
    public $timestamps = true;

    const CREATED_AT = 'appliance_script_parameters_created_at';
    const UPDATED_AT = 'appliance_script_parameters_updated_at';
    const DELETED_AT = 'appliance_script_parameters_deleted_at';

    public function version()
    {
        return $this->belongsTo(
            ApplianceVersion::class,
            'appliance_version_id',
            'appliance_script_parameters_appliance_version_id'
        );
    }

    public function databaseNames()
    {
    }
}
