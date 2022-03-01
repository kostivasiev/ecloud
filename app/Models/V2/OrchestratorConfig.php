<?php

namespace App\Models\V2;

use App\Traits\V2\CustomKey;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use UKFast\Api\Auth\Consumer;
use UKFast\DB\Ditto\Factories\FilterFactory;
use UKFast\DB\Ditto\Factories\SortFactory;
use UKFast\DB\Ditto\Filter;
use UKFast\DB\Ditto\Filterable;
use UKFast\DB\Ditto\Sortable;

/**
 * Class OrchestratorConfig
 * @package App\Models\V2
 */
class OrchestratorConfig extends Model implements Filterable, Sortable
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

    /**
     * @param FilterFactory $factory
     * @return array|Filter[]
     */
    public function filterableColumns(FilterFactory $factory): array
    {
        return [
            $factory->create('id', Filter::$stringDefaults),
            $factory->create('reseller_id', Filter::$numericDefaults),
            $factory->create('employee_id', Filter::$numericDefaults),
            $factory->create('locked', Filter::$numericDefaults),
            $factory->create('deploy_on', Filter::$dateDefaults),
        ];
    }

    /**
     * @param SortFactory $factory
     * @return array|\UKFast\DB\Ditto\Sort[]
     * @throws \UKFast\DB\Ditto\Exceptions\InvalidSortException
     */
    public function sortableColumns(SortFactory $factory): array
    {
        return [
            $factory->create('id'),
            $factory->create('reseller_id'),
            $factory->create('employee_id'),
            $factory->create('locked'),
            $factory->create('deploy_on'),
        ];
    }

    /**
     * @param SortFactory $factory
     * @return array|\UKFast\DB\Ditto\Sort|\UKFast\DB\Ditto\Sort[]|null
     * @throws \UKFast\DB\Ditto\Exceptions\InvalidSortException
     */
    public function defaultSort(SortFactory $factory): array
    {
        return [
            $factory->create('created_at', 'desc'),
        ];
    }

    public function databaseNames(): array
    {
        return [
            'id' => 'id',
            'reseller_id' => 'reseller_id',
            'employee_id' => 'employee_id',
            'data' => 'data',
            'locked' => 'locked',
            'deploy_on' => 'deploy_on',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
        ];
    }
}
