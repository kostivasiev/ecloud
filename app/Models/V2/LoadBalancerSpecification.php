<?php

namespace App\Models\V2;

use App\Traits\V2\CustomKey;
use App\Traits\V2\DefaultName;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use UKFast\Sieve\Searchable;
use UKFast\Sieve\Sieve;

/**
 * Class AvailabilityZoneCapacity
 * @package App\Models\V2
 * @method static find(string $routerId)
 * @method static findOrFail(string $routerUuid)
 */
class LoadBalancerSpecification extends Model implements Searchable
{
    use CustomKey, HasFactory, DefaultName, SoftDeletes;

    public $keyPrefix = 'lbs';
    protected $keyType = 'string';
    protected $connection = 'ecloud';
    public $incrementing = false;
    public $timestamps = true;

    protected $fillable = [
        'id',
        'name',
        'description',
        'node_count',
        'cpu',
        'ram',
        'hdd',
        'iops',
        'image_id'
    ];

    protected $casts = [
        'id' => 'string',
        'name' => 'string',
        'description' => 'string',
        'node_count' => 'integer',
        'cpu' => 'integer',
        'ram' => 'integer',
        'hdd' => 'integer',
        'iops' => 'integer',
        'image_id' => 'string',
    ];

    public function loadBalancers()
    {
        return $this->hasMany(LoadBalancer::class, 'load_balancer_spec_id', 'id');
    }

    public function sieve(Sieve $sieve)
    {
        $sieve->configure(fn ($filter) => [
            'id' => $filter->string(),
            'name' => $filter->string(),
            'description' => $filter->string(),
            'node_count' => $filter->numeric(),
            'cpu' => $filter->numeric(),
            'ram' => $filter->numeric(),
            'hdd' => $filter->numeric(),
            'iops' => $filter->numeric(),
            'created_at' => $filter->date(),
            'updated_at' => $filter->date(),
        ]);
    }
}
