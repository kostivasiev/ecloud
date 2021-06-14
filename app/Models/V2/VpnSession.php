<?php

namespace App\Models\V2;

use App\Traits\V2\CustomKey;
use App\Traits\V2\DefaultName;
use App\Traits\V2\DeletionRules;
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

class VpnSession extends Model implements Filterable, Sortable
{
    use CustomKey, SoftDeletes, DefaultName, DeletionRules, Syncable, Taskable;

    public $keyPrefix = 'vpns';

    public function __construct(array $attributes = [])
    {
        $this->timestamps = true;
        $this->incrementing = false;
        $this->keyType = 'string';
        $this->connection = 'ecloud';
        $this->fillable = [
            'id',
            'name',
            'vpn_service_id',
            'vpn_endpoint_id',
            'remote_ip',
            'remote_networks',
            'local_networks',
        ];
        parent::__construct($attributes);
    }

    public function vpnService()
    {
        // @todo Requires https://gitlab.devops.ukfast.co.uk/ukfast/api.ukfast/ecloud/-/issues/907
        return $this->belongsTo(VpnService::class);
    }

    public function vpnEndpoints()
    {
        // @todo Requires https://gitlab.devops.ukfast.co.uk/ukfast/api.ukfast/ecloud/-/issues/908
        // return $this->hasMany(VpnEndpoints::class);
    }

    /**
     * @param $query
     * @param $user
     * @return mixed
     */
    public function scopeForUser($query, Consumer $user)
    {
        if (!$user->isScoped()) {
            return $query;
        }
        return $query->whereHas('vpnService.router.vpc', function ($query) use ($user) {
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
            $factory->create('vpn_service_id', Filter::$stringDefaults),
            $factory->create('vpn_endpoint_id', Filter::$stringDefaults),
            $factory->create('remote_ip', Filter::$stringDefaults),
            $factory->create('remote_networks', Filter::$stringDefaults),
            $factory->create('local_networks', Filter::$stringDefaults),
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
            $factory->create('vpn_service_id'),
            $factory->create('vpn_endpoint_id'),
            $factory->create('remote_ip'),
            $factory->create('remote_networks'),
            $factory->create('local_networks'),
            $factory->create('created_at'),
            $factory->create('updated_at'),
        ];
    }

    /**
     * @param SortFactory $factory
     * @return array|\UKFast\DB\Ditto\Sort|\UKFast\DB\Ditto\Sort[]|null
     */
    public function defaultSort(SortFactory $factory)
    {
        return [
            $factory->create('id', 'asc'),
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
            'vpn_service_id' => 'vpn_service_id',
            'vpn_endpoint_id' => 'vpn_endpoint_id',
            'remote_ip' => 'remote_ip',
            'remote_networks' => 'remote_networks',
            'local_networks' => 'local_networks',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
        ];
    }
}
