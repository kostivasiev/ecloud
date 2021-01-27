<?php

namespace App\Models\V2;

use App\Events\V2\Router\Creating;
use App\Events\V2\Router\Created;
use App\Events\V2\Router\Deleted;
use App\Events\V2\Router\Saved;
use App\Events\V2\Router\Saving;
use App\Traits\V2\CustomKey;
use App\Traits\V2\DefaultAvailabilityZone;
use App\Traits\V2\DefaultName;
use App\Traits\V2\DeletionRules;
use App\Traits\V2\Syncable;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;
use UKFast\DB\Ditto\Factories\FilterFactory;
use UKFast\DB\Ditto\Factories\SortFactory;
use UKFast\DB\Ditto\Filter;
use UKFast\DB\Ditto\Filterable;
use UKFast\DB\Ditto\Sortable;

/**
 * Class Router
 * @package App\Models\V2
 * @method static find(string $routerId)
 * @method static findOrFail(string $routerUuid)
 * @method static forUser(string $user)
 */
class Router extends Model implements Filterable, Sortable
{
    use CustomKey, SoftDeletes, DefaultName, DefaultAvailabilityZone, DeletionRules, Syncable;

    public $keyPrefix = 'rtr';
    public $incrementing = false;
    public $timestamps = true;
    protected $keyType = 'string';
    protected $connection = 'ecloud';
    protected $fillable = [
        'id',
        'name',
        'vpc_id',
        'availability_zone_id',
        'deployed',
    ];

    protected $appends = [
        'available'
    ];

    protected $casts = [
        'deployed' => 'boolean',
    ];

    protected $dispatchesEvents = [
        'creating' => Creating::class,
        'created' => Created::class,
        'saved' => Saved::class,
        'deleted' => Deleted::class,
        'saving' => Saving::class,
    ];

    public $children = [
        'vpns',
    ];

    public function availabilityZone()
    {
        return $this->belongsTo(AvailabilityZone::class);
    }

    public function vpns()
    {
        return $this->hasMany(Vpn::class);
    }

    public function firewallPolicies()
    {
        return $this->hasMany(FirewallPolicy::class);
    }

    public function firewallRules()
    {
        return $this->hasMany(FirewallRule::class);
    }

    public function vpc()
    {
        return $this->belongsTo(Vpc::class);
    }

    public function networks()
    {
        return $this->hasMany(Network::class);
    }

    /**
     * @return bool
     * @throws \Exception
     * @see https://vdc-download.vmware.com/vmwb-repository/dcr-public/9e1c6bcc-85db-46b6-bc38-d6d2431e7c17/30af91b5-3a91-4d5d-8ed5-a7d806764a16/api_includes/types_LogicalRouterState.html
     * When the configuration is actually in effect, the state will change to "success".
     */
    public function getAvailableAttribute()
    {
        if (is_null($this->availabilityZone)) {
            return false;
        }

        try {
            $response = $this->availabilityZone->nsxService()->get(
                'policy/api/v1/infra/tier-1s/' . $this->getKey() . '/state'
            );
            $response = json_decode($response->getBody()->getContents());
            if (!isset($response->tier1_state->state)) {
                throw new \Exception('Failed to get state for ' . $this->id);
            }
            return $response->tier1_state->state == 'in_sync';
        } catch (GuzzleException $exception) {
            Log::info('Router available state response', [
                'id' => $this->getKey(),
                'response' => json_decode($exception->getResponse()->getBody()->getContents())->details,
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
            $query->whereHas('vpc', function ($query) use ($user) {
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
            $factory->create('vpc_id', Filter::$stringDefaults),
            $factory->create('availability_zone_id', Filter::$stringDefaults),
            $factory->create('deployed', Filter::$enumDefaults),
            $factory->create('created_at', Filter::$dateDefaults),
            $factory->create('updated_at', Filter::$dateDefaults),
        ];
    }

    /**
     * @param SortFactory $factory
     * @return array|\UKFast\DB\Ditto\Sort[]
     * @throws \UKFast\DB\Ditto\Exceptions\InvalidSortException
     */
    public function sortableColumns(SortFactory $factory)
    {
        return [
            $factory->create('id'),
            $factory->create('name'),
            $factory->create('vpc_id'),
            $factory->create('availability_zone_id'),
            $factory->create('deployed'),
            $factory->create('created_at'),
            $factory->create('updated_at'),
        ];
    }

    /**
     * @param SortFactory $factory
     * @return array|\UKFast\DB\Ditto\Sort|\UKFast\DB\Ditto\Sort[]|null
     * @throws \UKFast\DB\Ditto\Exceptions\InvalidSortException
     */
    public function defaultSort(SortFactory $factory)
    {
        return [
            $factory->create('name', 'asc'),
        ];
    }

    public function databaseNames()
    {
        return [
            'id' => 'id',
            'name' => 'name',
            'vpc_id' => 'vpc_id',
            'availability_zone_id' => 'availability_zone_id',
            'deployed' => 'deployed',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
        ];
    }
}
