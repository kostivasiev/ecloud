<?php

namespace App\Models\V2;

use App\Events\V2\FirewallRulePort\Deleted;
use App\Events\V2\FirewallRulePort\Deleting;
use App\Events\V2\FirewallRulePort\Saved;
use App\Events\V2\FirewallRulePort\Saving;
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
 * Class FirewallRulePort
 * @package App\Models\V2
 * @method static findOrFail(string $firewallRuleId)
 * @method static forUser($request)
 */
class FirewallRulePort extends Model implements Filterable, Sortable
{
    use CustomKey, SoftDeletes, DefaultName;

    const ICMP_MESSAGE_TYPE_ECHO_REQUEST = 8;
    public $keyPrefix = 'fwrp';
    public $incrementing = false;
    public $timestamps = true;
    protected $keyType = 'string';
    protected $connection = 'ecloud';
    protected $fillable = [
        'id',
        'name',
        'firewall_rule_id',
        'protocol',
        'source',
        'destination'
    ];

    public function firewallRule()
    {
        return $this->belongsTo(FirewallRule::class);
    }

    public function scopeForUser($query, Consumer $user)
    {
        if (!$user->isScoped()) {
            return $query;
        }
        return $query->whereHas('firewallRule.firewallPolicy.router.vpc', function ($query) use ($user) {
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
            $factory->create('id', Filter::$enumDefaults),
            $factory->create('name', Filter::$stringDefaults),
            $factory->create('firewall_rule_id', Filter::$enumDefaults),
            $factory->create('source', Filter::$stringDefaults),
            $factory->create('destination', Filter::$stringDefaults),
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
            $factory->create('firewall_rule_id'),
            $factory->create('source'),
            $factory->create('destination'),
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
            $factory->create('name'),
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
            'firewall_rule_id' => 'firewall_rule_id',
            'source' => 'source',
            'destination' => 'destination',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
            'deleted_at' => 'deleted_at',
        ];
    }
}
