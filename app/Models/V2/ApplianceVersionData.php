<?php

namespace App\Models\V2;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApplianceVersionData extends Model
{
    use HasFactory, SoftDeletes;

    public function __construct(array $attributes = [])
    {
        $this->incrementing = false;
        $this->connection = 'ecloud';
        $this->table = 'appliance_version_data';
        parent::__construct($attributes);
    }

    public function applianceVersion()
    {
        return $this->belongsTo(
            ApplianceVersion::class,
            'appliance_version_uuid',
            'appliance_version_uuid'
        );
    }
}
