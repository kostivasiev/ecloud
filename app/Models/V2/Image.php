<?php

namespace App\Models\V2;

use App\Events\V2\Image\Deleted;
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

/**
 * Class Image
 * @package App\Models\V2
 */
class Image extends Model implements Searchable, ResellerScopeable
{
    use HasFactory, CustomKey, SoftDeletes, DeletionRules, DefaultName, Syncable, Taskable;

    public string $keyPrefix = 'img';

    const VISIBILITY_PUBLIC = 'public';
    const VISIBILITY_PRIVATE = 'private';

    const PLATFORM_LINUX = 'Linux';
    const PLATFORM_WINDOWS = 'Windows';

    protected $casts = [
        'active' => 'boolean',
        'public' => 'boolean',
        'license_id' => 'integer'
    ];

    public function __construct(array $attributes = [])
    {
        $this->incrementing = false;
        $this->keyType = 'string';
        $this->connection = 'ecloud';

        $this->attributes = [
            'visibility' => static::VISIBILITY_PRIVATE
        ];

        $this->fillable([
            'id',
            'name',
            'vpc_id',
            'logo_uri',
            'documentation_uri',
            'description',
            'script_template',
            'readiness_script',
            'vm_template',
            'platform',
            'active',
            'public',
            'visibility',
            'publisher'
        ]);

        $this->dispatchesEvents = [
            'deleted' => Deleted::class,
        ];

        parent::__construct($attributes);
    }

    public function getResellerId(): int
    {
        return $this->vpc !== null ? $this->vpc->getResellerId() : 0;
    }

    public function vpc()
    {
        return $this->belongsTo(Vpc::class);
    }

    /**
     * Pivot table image_availability_zone
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function availabilityZones()
    {
        return $this->belongsToMany(AvailabilityZone::class);
    }

    public function instances()
    {
        return $this->hasMany(Instance::class);
    }

    public function imageParameters()
    {
        return $this->hasMany(ImageParameter::class);
    }

    public function imageMetadata()
    {
        return $this->hasMany(ImageMetadata::class);
    }

    public function software()
    {
        return $this->belongsToMany(Software::class);
    }

    /**
     * Get a single metadata value by key or return all as key => value collection
     * @param $key
     * @return \Illuminate\Database\Eloquent\HigherOrderBuilderProxy|\Illuminate\Support\Collection|mixed
     */
    public function getMetadata($key)
    {
        if ($key) {
            return $this->hasMany(ImageMetadata::class)->where('key', $key)->firstOr(fn() => new ImageMetadata(['value' => null]))->value;
        }

        return $this->hasMany(ImageMetadata::class)->pluck('key', 'value')->flip();
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

        $query->where('public', true)->where('active', true);

        $query->where(function ($query) use ($user) {
            $query->whereHas('vpc', function ($query) use ($user) {
                $query->where('reseller_id', $user->resellerId());
            });
            $query->orWhere('visibility', Image::VISIBILITY_PUBLIC);
        });

        return $query;
    }

    /**
     * @return int|null
     */
    public function getLicenseIDAttribute()
    {
        if ($this->imageMetadata()->where('key', 'ukfast.license.id')->exists()) {
            return (int) $this->imageMetadata()->where('key', 'ukfast.license.id')->first()->value;
        }

        return null;
    }

    public function sieve(Sieve $sieve)
    {
        $sieve->configure(fn ($filter) => [
            'id' => $filter->string(),
            'name' => $filter->string(),
            'vpc_id' => $filter->string(),
            'logo_uri' => $filter->string(),
            'documentation_uri' => $filter->string(),
            'description' => $filter->string(),
            'script_template' => $filter->string(),
            'readiness_script' => $filter->string(),
            'vm_template' => $filter->string(),
            'platform' => $filter->string(),
            'active' => $filter->boolean(),
            'public' => $filter->boolean(),
            'visibility' => $filter->boolean(),
            'publisher' => $filter->string(),
            'created_at' => $filter->date(),
            'updated_at' => $filter->date(),
        ]);
    }
}
