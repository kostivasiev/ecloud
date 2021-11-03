<?php

namespace App\Models\V2;

use App\Events\V2\NetworkRule\Deleted;
use App\Traits\V2\CustomKey;
use App\Traits\V2\DefaultName;
use App\Traits\V2\DeletionRules;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use UKFast\DB\Ditto\Factories\FilterFactory;
use UKFast\DB\Ditto\Factories\SortFactory;
use UKFast\DB\Ditto\Filter;
use UKFast\DB\Ditto\Filterable;
use UKFast\DB\Ditto\Sortable;

class NetworkRule extends Model implements Filterable, Sortable, Manageable
{
    use CustomKey, SoftDeletes, DefaultName, DeletionRules;

    public string $keyPrefix = 'nr';

    const TYPE_DHCP = 'dhcp';
    const TYPE_CATCHALL = 'catchall';

    public function __construct(array $attributes = [])
    {
        $this->incrementing = false;
        $this->keyType = 'string';
        $this->connection = 'ecloud';
        $this->fillable = [
            'id',
            'network_policy_id',
            'name',
            'sequence',
            'source',
            'destination',
            'action',
            'direction',
            'enabled',
            'type'
        ];
        $this->casts = [
            'sequence' => 'integer',
            'enabled' => 'boolean',
        ];
        parent::__construct($attributes);
    }

    public function networkPolicy()
    {
        return $this->belongsTo(NetworkPolicy::class);
    }

    public function networkRulePorts()
    {
        return $this->hasMany(NetworkRulePort::class);
    }

    /**
     * @param $query
     * @param $user
     * @return mixed
     */
    public function scopeForUser($query, $user)
    {
        if (!$user->isScoped()) {
            return $query;
        }

        return $query
            ->whereHas('networkPolicy.network.router.vpc', function ($query) use ($user) {
                $query->where('reseller_id', $user->resellerId());
            })
            ->whereHas('networkPolicy.network.router', function ($query) {
                $query->where('is_management', false);
            });
    }

    public function isManaged() :bool
    {
        return (bool) $this->networkPolicy->network->router->isManaged();
    }

    public function isHidden(): bool
    {
        return $this->isManaged();
    }

    public function filterableColumns(FilterFactory $factory)
    {
        return [
            $factory->create('id', Filter::$stringDefaults),
            $factory->create('network_policy_id', Filter::$stringDefaults),
            $factory->create('name', Filter::$stringDefaults),
            $factory->create('sequence', Filter::$numericDefaults),
            $factory->create('source', Filter::$stringDefaults),
            $factory->create('destination', Filter::$stringDefaults),
            $factory->create('action', Filter::$stringDefaults),
            $factory->create('direction', Filter::$stringDefaults),
            $factory->create('enabled', Filter::$numericDefaults),
            $factory->create('type', Filter::$stringDefaults),
            $factory->create('created_at', Filter::$dateDefaults),
            $factory->create('updated_at', Filter::$dateDefaults),
        ];
    }

    public function sortableColumns(SortFactory $factory)
    {
        return [
            $factory->create('id'),
            $factory->create('network_policy_id'),
            $factory->create('name'),
            $factory->create('sequence'),
            $factory->create('source'),
            $factory->create('destination'),
            $factory->create('action'),
            $factory->create('direction'),
            $factory->create('enabled'),
            $factory->create('type'),
            $factory->create('created_at'),
            $factory->create('updated_at'),
        ];
    }

    public function defaultSort(SortFactory $factory)
    {
        return [
            $factory->create('sequence'),
        ];
    }

    public function databaseNames()
    {
        return [
            'id' => 'id',
            'network_policy_id' => 'network_policy_id',
            'name' => 'name',
            'sequence' => 'sequence',
            'source' => 'source',
            'destination' => 'destination',
            'action' => 'action',
            'direction' => 'direction',
            'enabled' => 'enabled',
            'type' => 'type',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
        ];
    }
}
