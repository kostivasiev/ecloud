<?php

namespace App\Models\V2;

use App\Events\V2\Network\Created;
use App\Events\V2\Network\Creating;
use App\Traits\V2\CustomKey;
use App\Traits\V2\DefaultName;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;
use UKFast\DB\Ditto\Exceptions\InvalidSortException;
use UKFast\DB\Ditto\Factories\FilterFactory;
use UKFast\DB\Ditto\Factories\SortFactory;
use UKFast\DB\Ditto\Filter;
use UKFast\DB\Ditto\Filterable;
use UKFast\DB\Ditto\Sort;
use UKFast\DB\Ditto\Sortable;

/**
 * @method static findOrFail(string $networkId)
 * @method static forUser(string $user)
 * @method static find($id)
 * @method static where(string $string, string $string1, $id)
 */
class Network extends Model implements Filterable, Sortable
{
    use CustomKey, SoftDeletes, DefaultName;

    public $keyPrefix = 'net';
    protected $keyType = 'string';
    protected $connection = 'ecloud';
    public $incrementing = false;
    public $timestamps = true;

    protected $fillable = [
        'id',
        'name',
        'router_id',
        'subnet'
    ];

    protected $dispatchesEvents = [
        'creating' => Creating::class,
        'created' => Created::class,
    ];

    public function router()
    {
        return $this->belongsTo(Router::class);
    }

    public function nics()
    {
        return $this->hasMany(Nic::class);
    }

    /**
     * @return bool
     * @throws Exception
     * @see https://vdc-download.vmware.com/vmwb-repository/dcr-public/9e1c6bcc-85db-46b6-bc38-d6d2431e7c17/30af91b5-3a91-4d5d-8ed5-a7d806764a16/api_includes/method_GetSegmentState.html
     * When the configuration is actually in effect, the state will change to "success".
     */
    public function getAvailableAttribute()
    {
        try {
            $response = $this->router->availabilityZone->nsxService()->get(
                'policy/api/v1/infra/tier-1s/' . $this->router->getKey() . '/segments/' . $this->getKey() . '/state'
            );
            $response = json_decode($response->getBody()->getContents());
            return in_array($response->state, ['in_sync', 'success']);
        } catch (GuzzleException $exception) {
            Log::info('Segment state response', [
                'id' => $this->getKey(),
                'response' => json_decode($exception->getResponse()->getBody()->getContents()),
            ]);
            return false;
        }
    }

    /**
     * @param $query
     * @param $user
     * @return mixed
     */
    public function scopeForUser($query, $user)
    {
        if (!empty($user->resellerId)) {
            $query->whereHas('router.vpc', function ($query) use ($user) {
                $resellerId = filter_var($user->resellerId, FILTER_SANITIZE_NUMBER_INT);
                if (!empty($resellerId)) {
                    $query->where('reseller_id', '=', $resellerId);
                }
            });
        }

        return $query;
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
