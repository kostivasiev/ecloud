<?php

namespace App\Models\V2;

use App\Events\V2\FirewallRule\Created;
use App\Events\V2\FirewallRule\Creating;
use App\Traits\V2\CustomKey;
use App\Traits\V2\DefaultName;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use UKFast\DB\Ditto\Factories\FilterFactory;
use UKFast\DB\Ditto\Factories\SortFactory;
use UKFast\DB\Ditto\Filter;
use UKFast\DB\Ditto\Filterable;
use UKFast\DB\Ditto\Sortable;

/**
 * Class FirewallRule
 * @package App\Models\V2
 * @method static findOrFail(string $firewallRuleId)
 * @method static forUser($request)
 */
class FirewallRule extends Model implements Filterable, Sortable
{
    use CustomKey, SoftDeletes, DefaultName;

    public $keyPrefix = 'fwr';
    public $incrementing = false;
    public $timestamps = true;
    protected $keyType = 'string';
    protected $connection = 'ecloud';
    protected $fillable = [
        'name',
        'router_id',
        'deployed',
        'firewall_policy_id',
        'router_id',
        'service_type',
        'source',
        'source_ports',
        'destination',
        'destination_ports',
        'action',
        'direction',
        'enabled',
    ];

    protected $casts = [
        'deployed' => 'boolean',
        'enabled' => 'boolean',
    ];

    protected $dispatchesEvents = [
        'creating' => Creating::class,
        'created' => Created::class,
    ];

    public function router()
    {
        return $this->belongsTo(Router::class);
    }

    public function firewallPolicy()
    {
        return $this->belongsTo(FirewallPolicy::class);
    }

    public function scopeForUser($query, $user)
    {
        if (!empty($user->resellerId)) {
            $query->whereHas('router', function ($query) use ($user) {
                $query->whereHas('vpc', function ($query) use ($user) {
                    $resellerId = filter_var($user->resellerId, FILTER_SANITIZE_NUMBER_INT);
                    if (!empty($resellerId)) {
                        $query->where('reseller_id', '=', $resellerId);
                    }
                });
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
            $factory->create('id', Filter::$enumDefaults),
            $factory->create('name', Filter::$stringDefaults),
            $factory->create('firewall_policy_id', Filter::$enumDefaults),
            $factory->create('router_id', Filter::$enumDefaults),
            $factory->create('deployed', Filter::$numericDefaults),
            $factory->create('service_type', Filter::$enumDefaults),
            $factory->create('source', Filter::$stringDefaults),
            $factory->create('source_ports', Filter::$stringDefaults),
            $factory->create('destination', Filter::$stringDefaults),
            $factory->create('destination_ports', Filter::$stringDefaults),
            $factory->create('action', Filter::$stringDefaults),
            $factory->create('direction', Filter::$stringDefaults),
            $factory->create('enabled', Filter::$numericDefaults),
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
            $factory->create('firewall_policy_id'),
            $factory->create('router_id'),
            $factory->create('deployed'),
            $factory->create('service_type'),
            $factory->create('source'),
            $factory->create('source_ports'),
            $factory->create('destination'),
            $factory->create('destination_ports'),
            $factory->create('action'),
            $factory->create('direction'),
            $factory->create('enabled'),
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
            'firewall_policy_id' => 'firewall_policy_id',
            'service_type' => 'service_type',
            'source' => 'source',
            'source_ports' => 'source_ports',
            'destination' => 'destination',
            'destination_ports' => 'destination_ports',
            'action' => 'action',
            'direction' => 'direction',
            'enabled' => 'enabled',
            'deployed' => 'deployed',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
            'deleted_at' => 'deleted_at',
        ];
    }
}
