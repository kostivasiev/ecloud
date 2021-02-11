<?php

namespace App\Models\V2;

use App\Traits\V2\CustomKey;
use App\Traits\V2\DefaultName;
use App\Traits\V2\DeletionRules;
use App\Traits\V2\Syncable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use UKFast\DB\Ditto\Factories\FilterFactory;
use UKFast\DB\Ditto\Factories\SortFactory;
use UKFast\DB\Ditto\Factory;
use UKFast\DB\Ditto\Filter;
use UKFast\DB\Ditto\Filterable;
use UKFast\DB\Ditto\Sortable;

/**
 * Class NetworkAclRule
 * @package App\Models\V2
 * @method static NetworkAclRule find(string $id)
 * @method static NetworkAclRule findOrFail(string $id)
 * @method static NetworkAclRule forUser(mixed $user)
 * @method static NetworkAclRule get()
 * @property string id
 * @property string network_acl_policy_id
 * @property string name
 * @property integer sequence
 * @property string source
 * @property string destination
 * @property string action
 * @property boolean enabled
 * @property string created_at
 * @property string updated_at
 */
class NetworkAclRule extends Model implements Filterable, Sortable
{
    use CustomKey, SoftDeletes, DefaultName, DeletionRules, Syncable;

    public $keyPrefix = 'nar';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $connection = 'ecloud';
    protected $fillable = [
        'id',
        'network_acl_policy_id',
        'name',
        'sequence',
        'source',
        'destination',
        'action',
        'enabled',
    ];
    protected $casts = [
        'sequence' => 'integer',
        'enabled' => 'boolean',
    ];

    public function networkAclPolicy(): BelongsTo
    {
        return $this->belongsTo(NetworkAclPolicy::class);
    }

    /**
     * @param $query
     * @param $user
     * @return mixed
     */
    public function scopeForUser($query, $user)
    {
        if (!empty($user->resellerId)) {
            $query->whereHas('networkAclPolicy.vpc', function ($query) use ($user) {
                $resellerId = filter_var($user->resellerId, FILTER_SANITIZE_NUMBER_INT);
                if (!empty($resellerId)) {
                    $query->where('reseller_id', '=', $resellerId);
                }
            });
        }
        return $query;
    }

    public function filterableColumns(FilterFactory $factory)
    {
        return [
            $factory->create('id', Filter::$stringDefaults),
            $factory->create('network_acl_policy_id', Filter::$stringDefaults),
            $factory->create('name', Filter::$stringDefaults),
            $factory->create('sequence', Filter::$numericDefaults),
            $factory->create('source', Filter::$stringDefaults),
            $factory->create('destination', Filter::$stringDefaults),
            $factory->create('action', Filter::$stringDefaults),
            $factory->create('enabled', Filter::$numericDefaults),
            $factory->create('created_at', Filter::$dateDefaults),
            $factory->create('updated_at', Filter::$dateDefaults),
        ];
    }

    public function sortableColumns(SortFactory $factory)
    {
        return [
            $factory->create('id'),
            $factory->create('network_acl_policy_id'),
            $factory->create('name'),
            $factory->create('sequence'),
            $factory->create('source'),
            $factory->create('destination'),
            $factory->create('action'),
            $factory->create('enabled'),
            $factory->create('created_at'),
            $factory->create('updated_at'),
        ];
    }

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
            'network_acl_policy_id' => 'network_acl_policy_id',
            'name' => 'name',
            'sequence' => 'sequence',
            'source' => 'source',
            'destination' => 'destination',
            'action' => 'action',
            'enabled' => 'enabled',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
        ];
    }
}
