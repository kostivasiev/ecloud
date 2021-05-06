<?php

namespace App\Models\V2;

use App\Events\V2\Network\Created;
use App\Events\V2\Network\Creating;
use App\Events\V2\Network\Deleted;
use App\Events\V2\Network\Deleting;
use App\Events\V2\Network\Saved;
use App\Events\V2\Network\Saving;
use App\Traits\V2\CustomKey;
use App\Traits\V2\DefaultName;
use App\Traits\V2\DeletionRules;
use App\Traits\V2\Syncable;
use App\Traits\V2\Taskable;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;
use UKFast\Api\Auth\Consumer;
use UKFast\DB\Ditto\Exceptions\InvalidSortException;
use UKFast\DB\Ditto\Factories\FilterFactory;
use UKFast\DB\Ditto\Factories\SortFactory;
use UKFast\DB\Ditto\Filter;
use UKFast\DB\Ditto\Filterable;
use UKFast\DB\Ditto\Sort;
use UKFast\DB\Ditto\Sortable;

class Network extends Model implements Filterable, Sortable
{
    use CustomKey, SoftDeletes, DefaultName, DeletionRules, Syncable, Taskable;

    public $keyPrefix = 'net';
    public $incrementing = false;
    public $children = [
        'nics',
    ];
    protected $keyType = 'string';
    protected $connection = 'ecloud';
    protected $fillable = [
        'id',
        'name',
        'router_id',
        'subnet'
    ];
    protected $dispatchesEvents = [
        'creating' => Creating::class,
        'created' => Created::class,
        'saving' => Saving::class,
        'saved' => Saved::class,
        'deleted' => Deleted::class,
        'deleting' => Deleting::class,
    ];

    public function router()
    {
        return $this->belongsTo(Router::class);
    }

    public function nics()
    {
        return $this->hasMany(Nic::class);
    }

    public function networkPolicy()
    {
        return $this->hasOne(NetworkPolicy::class);
    }

    /**
     * @return bool
     * @throws \Exception
     * @see https://vdc-download.vmware.com/vmwb-repository/dcr-public/9e1c6bcc-85db-46b6-bc38-d6d2431e7c17/30af91b5-3a91-4d5d-8ed5-a7d806764a16/api_includes/method_GetSegmentState.html
     * When the configuration is actually in effect, the state will change to "success".
     */
/*    public function getAvailableAttribute()
    {
        try {
            $response = $this->router->availabilityZone->nsxService()->get(
                'policy/api/v1/infra/tier-1s/' . $this->router->id . '/segments/' . $this->id . '/state'
            );
            $response = json_decode($response->getBody()->getContents());
            return in_array($response->state, ['in_sync', 'success']);
        } catch (GuzzleException $exception) {
            Log::info('Segment state response', [
                'id' => $this->id,
                'response' => json_decode($exception->getResponse()->getBody()->getContents()),
            ]);
            return false;
        }
    }*/

    /**
     * @param $query
     * @param Consumer $user
     * @return mixed
     */
    public function scopeForUser($query, Consumer $user)
    {
        if (!$user->isScoped()) {
            return $query;
        }
        return $query->whereHas('router.vpc', function ($query) use ($user) {
            $query->where('reseller_id', $user->resellerId());
        });
    }

    /**
     * @param FilterFactory $factory
     * @return array|Filter[]
     */
    public function filterableColumns(FilterFactory $factory)
    {
        return [
            $factory->create('id', Filter::$stringDefaults),
            $factory->create('name', Filter::$stringDefaults),
            $factory->create('router_id', Filter::$stringDefaults),
            $factory->create('subnet', Filter::$stringDefaults),
            $factory->create('created_at', Filter::$dateDefaults),
            $factory->create('updated_at', Filter::$dateDefaults),
        ];
    }

    /**
     * @param SortFactory $factory
     * @return array|Sort[]
     * @throws InvalidSortException
     */
    public function sortableColumns(SortFactory $factory)
    {
        return [
            $factory->create('id'),
            $factory->create('name'),
            $factory->create('router_id'),
            $factory->create('subnet'),
            $factory->create('created_at'),
            $factory->create('updated_at'),
        ];
    }

    /**
     * @param SortFactory $factory
     * @return array|Sort|Sort[]|null
     */
    public function defaultSort(SortFactory $factory)
    {
        return [
            $factory->create('name', 'asc'),
        ];
    }

    /**
     * @return array|string[]
     */
    public function databaseNames()
    {
        return [
            'id' => 'id',
            'name' => 'name',
            'router_id' => 'router_id',
            'subnet' => 'subnet',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
        ];
    }
}
