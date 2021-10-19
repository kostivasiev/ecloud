<?php

namespace App\Models\V2;

use App\Events\V2\FirewallRule\Deleted;
use App\Events\V2\FirewallRule\Saved;
use App\Traits\V2\CustomKey;
use App\Traits\V2\DefaultName;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use UKFast\Api\Auth\Consumer;
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
        'id',
        'name',
        'sequence',
        'deployed',
        'firewall_policy_id',
        'source',
        'destination',
        'action',
        'direction',
        'enabled',
    ];

    protected $casts = [
        'deployed' => 'boolean',
        'enabled' => 'boolean',
    ];

    public function firewallPolicy()
    {
        return $this->belongsTo(FirewallPolicy::class);
    }

    public function firewallRulePorts()
    {
        return $this->hasMany(FirewallRulePort::class);
    }

    public function scopeForUser($query, Consumer $user)
    {
        if (!$user->isScoped()) {
            return $query;
        }

        return $query
            ->whereHas('firewallPolicy.router.vpc', function ($query) use ($user) {
                $query->where('reseller_id', $user->resellerId());
            })
            ->whereHas('firewallPolicy.router', function ($query) {
                $query->where('is_management', false);
            });
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
            $factory->create('sequence', Filter::$stringDefaults),
            $factory->create('firewall_policy_id', Filter::$enumDefaults),
            $factory->create('deployed', Filter::$numericDefaults),
            $factory->create('source', Filter::$stringDefaults),
            $factory->create('destination', Filter::$stringDefaults),
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
            $factory->create('sequence'),
            $factory->create('firewall_policy_id'),
            $factory->create('deployed'),
            $factory->create('source'),
            $factory->create('destination'),
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
            $factory->create('sequence'),
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
            'sequence' => 'sequence',
            'firewall_policy_id' => 'firewall_policy_id',
            'source' => 'source',
            'destination' => 'destination',
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
