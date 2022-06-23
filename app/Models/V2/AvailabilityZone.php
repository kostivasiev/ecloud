<?php

namespace App\Models\V2;

use App\Events\V2\AvailabilityZone\Created;
use App\Events\V2\AvailabilityZone\Creating;
use App\Events\V2\AvailabilityZone\Deleted;
use App\Services\V2\ArtisanService;
use App\Services\V2\ConjurerService;
use App\Services\V2\KingpinService;
use App\Services\V2\NsxService;
use App\Traits\V2\CustomKey;
use App\Traits\V2\DeletionRules;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use UKFast\Api\Auth\Consumer;
use UKFast\Sieve\Searchable;
use UKFast\Sieve\Sieve;

/**
 * Class AvailabilityZones
 * @package App\Models\V2
 * @method static findOrFail(string $zoneId)
 */
class AvailabilityZone extends Model implements Searchable, RegionAble
{
    use HasFactory, CustomKey, SoftDeletes, DeletionRules;

    public $keyPrefix = 'az';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $connection = 'ecloud';
    protected $fillable = [
        'id',
        'code',
        'name',
        'datacentre_site_id',
        'region_id',
        'is_public',
        'san_name',
        'ucs_compute_name',
    ];

    protected $dispatchesEvents = [
        'creating' => Creating::class,
        'created' => Created::class,
        'deleted' => Deleted::class,
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'datacentre_site_id' => 'integer',
    ];

    public $children = [
        'routers',
        'instances',
        'loadBalancers'
    ];

    /**
     * @var NsxService
     */
    protected $nsxService;

    /**
     * @var KingpinService
     */
    protected $kingpinService;

    /**
     * @var ConjurerService
     */
    protected $conjurerService;

    /**
     * @var ArtisanService
     */
    protected $artisanService;

    public function routers()
    {
        return $this->hasMany(Router::class);
    }

    public function routerThroughputs()
    {
        return $this->hasMany(RouterThroughput::class);
    }

    public function dhcps()
    {
        return $this->hasMany(Dhcp::class);
    }

    public function region()
    {
        return $this->belongsTo(Region::class);
    }

    public function credentials()
    {
        return $this->hasMany(Credential::class, 'resource_id', 'id');
    }

    public function instances()
    {
        return $this->hasMany(Instance::class);
    }

    public function loadBalancers()
    {
        return $this->hasMany(LoadBalancer::class);
    }

    public function availabilityZoneCapacities()
    {
        return $this->hasMany(AvailabilityZoneCapacity::class);
    }

    public function images()
    {
        return $this->belongsToMany(Image::class);
    }

    public function vpnProfileGroup()
    {
        return $this->hasMany(VpnProfileGroup::class);
    }

    public function resourceTiers()
    {
        return $this->hasMany(ResourceTier::class);
    }

    public function nsxService()
    {
        if (!$this->nsxService) {
            $this->nsxService = app()->makeWith(NsxService::class, [$this]);
        }
        return $this->nsxService;
    }

    public function kingpinService()
    {
        if (!$this->kingpinService) {
            $this->kingpinService = app()->makeWith(KingpinService::class, [$this]);
        }
        return $this->kingpinService;
    }

    public function conjurerService()
    {
        if (!$this->conjurerService) {
            $this->conjurerService = app()->makeWith(ConjurerService::class, [$this]);
        }
        return $this->conjurerService;
    }

    public function artisanService()
    {
        if (!$this->artisanService) {
            $this->artisanService = app()->makeWith(ArtisanService::class, [$this]);
        }
        return $this->artisanService;
    }

    public function products()
    {
        return Product::forAvailabilityZone($this);
    }

    public function hostSpecs()
    {
        return $this->belongsToMany(HostSpec::class);
    }

    /**
     * @param bool $advanced
     * @return string
     * @throws \Exception
     */
    public function getNsxEdgeClusterId(Bool $advanced = false, Bool $management = false): string
    {
        $tag = $advanced ?
            config('defaults.tag.edge-cluster.advanced'):
            config('defaults.tag.edge-cluster.default');
        if ($management) {
            $tag = $advanced ?
                config('defaults.tag.edge-cluster.management.advanced'):
                config('defaults.tag.edge-cluster.management.default');
        }
        $searchEdgeClusterResponse = json_decode($this->nsxService()->get(
            'api/v1/search/query?query=resource_type:EdgeCluster' .
            '%20AND%20tags.scope:' . config('defaults.tag.scope') .
            '%20AND%20tags.tag:' . $tag
        )->getBody()->getContents());

        if ($searchEdgeClusterResponse->result_count != 1) {
            throw new \Exception(
                'Failed to determine ' .
                ($advanced ? 'advanced networking' : 'standard') .
                ' edge cluster ID for availability zone ' . $this->id
            );
        }

        return $searchEdgeClusterResponse->results[0]->id;
    }

    public function getDefaultHostGroup(): HostGroup
    {
        $defaultHostGroup = null;
        $lastCapacity = null;
        foreach ($this->getAvailableHostGroups() as $hostGroup) {
            $capacity = $hostGroup->getAvailableCapacity();
            if ($defaultHostGroup === null ||
                ($capacity['ram']['percentage'] < $lastCapacity['ram']['percentage'] &&
                    $capacity['cpu']['percentage'] < $lastCapacity['cpu']['percentage'])) {
                $defaultHostGroup = $hostGroup;
                $lastCapacity = $capacity;
                continue;
            }
        }
        return $defaultHostGroup;
    }

    public function getAvailableHostGroups(): Collection
    {
        $hostGroups = [];
        $this->resourceTiers()
            ->each(function (ResourceTier $resourceTier) use (&$hostGroups) {
                $hostGroups = array_merge($hostGroups, $resourceTier->hostGroups->pluck('id')->toArray());
            });
        return HostGroup::find($hostGroups);
    }

    /**
     * @param $query
     * @param $user
     * @return mixed
     */
    public function scopeForUser($query, Consumer $user)
    {
        if ($user->isAdmin()) {
            return $query;
        }

        if (in_array($user->resellerId(), config('reseller.internal'))) {
            return $query;
        }

        return $query->whereHas('region', function ($query) {
            $query->where('is_public', '=', true);
        })->where('is_public', '=', true);
    }

    public function sieve(Sieve $sieve)
    {
        $sieve->configure(fn ($filter) => [
            'id' => $filter->string(),
            'code' => $filter->string(),
            'name' => $filter->string(),
            'datacentre_site_id' => $filter->numeric(),
            'region_id' => $filter->string(),
            'is_public' => $filter->numeric(),
            'san_name' => $filter->string(),
            'ucs_compute_name' => $filter->string(),
            'created_at' => $filter->date(),
            'updated_at' => $filter->date(),
        ]);
    }
}
