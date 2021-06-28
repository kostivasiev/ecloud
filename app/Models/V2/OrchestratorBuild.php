<?php

namespace App\Models\V2;

use App\Traits\V2\CustomKey;
use App\Traits\V2\Taskable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Orchestrator
 * @package App\Models\V2
 */
class OrchestratorBuild extends Model
{
    use CustomKey, SoftDeletes, Taskable;

    public string $keyPrefix = 'obuild';

    public function __construct(array $attributes = [])
    {
        $this->incrementing = false;
        $this->keyType = 'string';
        $this->connection = 'ecloud';

        $this->fillable([
            'id',
            'orchestrator_config_id',
            'state'
        ]);
        parent::__construct($attributes);
    }

    public function orchestratorConfig()
    {
        return $this->belongsTo(OrchestratorConfig::class);
    }
}
