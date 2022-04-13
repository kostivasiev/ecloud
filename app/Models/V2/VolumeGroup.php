<?php
namespace App\Models\V2;

use App\Traits\V2\CustomKey;
use App\Traits\V2\DefaultName;
use App\Traits\V2\DeletionRules;
use App\Traits\V2\Syncable;
use App\Traits\V2\Taskable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use UKFast\Api\Auth\Consumer;
use UKFast\Sieve\Searchable;
use UKFast\Sieve\Sieve;

class VolumeGroup extends Model implements Searchable, ResellerScopeable, AvailabilityZoneable, VpcAble
{
    use CustomKey, SoftDeletes, DefaultName, DeletionRules, Syncable, Taskable, HasFactory;

    public $keyPrefix = 'volgroup';
    protected $keyType = 'string';
    protected $connection = 'ecloud';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'name',
        'vpc_id',
        'availability_zone_id',
    ];

    public $children = [
        'volumes',
        'instances'
    ];

    public function getResellerId(): int
    {
        return $this->vpc->getResellerId();
    }

    public function availabilityZone()
    {
        return $this->belongsTo(AvailabilityZone::class);
    }

    public function vpc()
    {
        return $this->belongsTo(Vpc::class);
    }

    public function volumes()
    {
        return $this->hasMany(Volume::class);
    }

    public function instances()
    {
        return $this->hasMany(Instance::class);
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
        return $query->whereHas('vpc', function ($query) use ($user) {
            $query->where('reseller_id', $user->resellerId());
        });
    }

    public function sieve(Sieve $sieve)
    {
        $sieve->configure(fn ($filter) => [
            'id' => $filter->string(),
            'name' => $filter->string(),
            'vpc_id' => $filter->string(),
            'availability_zone_id' => $filter->string(),
            'created_at' => $filter->date(),
            'updated_at' => $filter->date(),
        ]);
    }

    public function getVolumeTotalAttribute()
    {
        return (int) $this->volumes->count();
    }
}
