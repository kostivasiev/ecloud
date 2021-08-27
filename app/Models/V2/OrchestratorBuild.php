<?php

namespace App\Models\V2;

use App\Support\Resource;
use App\Traits\V2\CustomKey;
use App\Traits\V2\Syncable;
use App\Traits\V2\Taskable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
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
            $factory->create('orchestrator_config_id', Filter::$stringDefaults),
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
            $factory->create('orchestrator_config_id'),
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


    public function render($definition) : Collection
    {
        return collect($definition)->map(function ($item, $key) {
            if ($key == 'id') {
                return $item;
            }
            if (is_string($item) && preg_match('/^\{(\w+)\.(\d+)\}$/', $item, $matches) === 1) {
                list($placeholder, $resource, $index) = $matches;

                if (isset($this->state[$resource]) && isset($this->state[$resource][$index])) {
                    //Check the resource exists - not sure this belongs in here really
                    $id = $this->state[$resource][$index];
                    $resource = Resource::classFromId($id)::find($id);
                    if (empty($resource)) {
                        throw new \Exception('Resource for placeholder ' . $placeholder .' was found in build state, but associated resource ' . $id . ' does not exist');
                    }

                    return $id;
                }

                throw new \Exception('Failed to render placeholder ' . $placeholder . ', resource was not found in the current build state.');
            }
            return $item;
        });
    }
}
