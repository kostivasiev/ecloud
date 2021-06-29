<?php

namespace App\Models\V2;

use App\Traits\V2\CustomKey;
use App\Traits\V2\Syncable;
use App\Traits\V2\Taskable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use UKFast\Api\Auth\Consumer;
use UKFast\DB\Ditto\Factories\FilterFactory;
use UKFast\DB\Ditto\Factories\SortFactory;
use UKFast\DB\Ditto\Filter;
use UKFast\DB\Ditto\Filterable;
use UKFast\DB\Ditto\Sortable;

/**
 * Class Orchestrator
 * @package App\Models\V2
 */
class OrchestratorBuild extends Model implements Filterable, Sortable
{
    use CustomKey, SoftDeletes, Syncable, Taskable;

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

        $this->casts = [
            'state' => 'array',
        ];

        parent::__construct($attributes);
    }

    public function orchestratorConfig()
    {
        return $this->belongsTo(OrchestratorConfig::class);
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
            'state' => 'state',
            'orchestrator_config_id' => 'orchestrator_config_id',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
        ];
    }

    /**
     * Update the state JSON, re-serealize and save
     * @param $resource
     * @param $index
     * @param $id
     */
    public function updateState($resource, $index, $id)
    {
        $state = $this->state;
        $state[$resource][$index] = $id;
        $this->state = $state;
        $this->save();
    }
}
