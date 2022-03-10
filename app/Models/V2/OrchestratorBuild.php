<?php

namespace App\Models\V2;

use App\Support\Resource;
use App\Traits\V2\CustomKey;
use App\Traits\V2\Syncable;
use App\Traits\V2\Taskable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use UKFast\Api\Auth\Consumer;
use UKFast\Sieve\Searchable;
use UKFast\Sieve\Sieve;

/**
 * Class Orchestrator
 * @package App\Models\V2
 */
class OrchestratorBuild extends Model implements Searchable
{
    use HasFactory, CustomKey, SoftDeletes, Syncable, Taskable;

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

    public function sieve(Sieve $sieve)
    {
        $sieve->configure(fn ($filter) => [
            'id' => $filter->string(),
            'orchestrator_config_id' => $filter->string(),
        ]);
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
