<?php

namespace App\Models\V2;

use App\Traits\V2\CustomKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use UKFast\Api\Auth\Consumer;
use UKFast\Sieve\Searchable;
use UKFast\Sieve\Sieve;

/**
 * Class OrchestratorConfig
 * @package App\Models\V2
 */
class OrchestratorConfig extends Model implements Searchable
{
    use HasFactory, CustomKey, SoftDeletes;

    public string $keyPrefix = 'oconf';

    public function __construct(array $attributes = [])
    {
        $this->incrementing = false;
        $this->keyType = 'string';
        $this->connection = 'ecloud';

        $this->fillable([
            'id',
            'reseller_id',
            'employee_id',
            'data',
            'locked',
            'deploy_on',
        ]);

        $this->casts = [
            'reseller_id' => 'integer',
            'employee_id' => 'integer',
            'locked' => 'boolean',
        ];
        parent::__construct($attributes);
    }

    public function orchestratorBuilds()
    {
        return $this->hasMany(OrchestratorBuild::class);
    }

    public function scopeForUser($query, Consumer $user)
    {
        return $query;
    }

    public function sieve(Sieve $sieve)
    {
        $sieve->configure(fn ($filter) => [
            'id' => $filter->string(),
            'reseller_id' => $filter->numeric(),
            'employee_id' => $filter->numeric(),
            'locked' => $filter->numeric(),
            'deploy_on' => $filter->date(),
        ]);
    }
}
