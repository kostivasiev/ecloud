<?php

namespace App\Models\V2;

use App\Traits\V2\CustomKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use UKFast\Sieve\Searchable;
use UKFast\Sieve\Sieve;

class ResourceTierHostGroup extends Model implements Searchable
{
    use HasFactory, CustomKey, SoftDeletes;

    public $keyPrefix = 'rthg';

    public function __construct(array $attributes = [])
    {
        $this->incrementing = false;
        $this->keyType = 'string';
        $this->connection = 'ecloud';
        $this->table = 'resource_tier_host_group';

        $this->fillable([
            'id',
            'resource_tier_id',
            'host_group_id',
        ]);

        parent::__construct($attributes);
    }

    public function resourceTier()
    {
        return $this->belongsTo(ResourceTier::class);
    }

    public function hostGroup()
    {
        return $this->belongsTo(HostGroup::class);
    }

    public function sieve(Sieve $sieve)
    {
        $sieve->configure(fn ($filter) => [
            'id' => $filter->string(),
            'resource_tier_id' => $filter->string(),
            'host_group_id' => $filter->string(),
            'created_at' => $filter->date(),
            'updated_at' => $filter->date(),
        ]);
    }
}
