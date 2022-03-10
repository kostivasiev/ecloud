<?php

namespace App\Models\V2;

use App\Traits\V2\CustomKey;
use App\Traits\V2\DefaultName;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use UKFast\Api\Auth\Consumer;
use UKFast\Sieve\Searchable;
use UKFast\Sieve\Sieve;

class Software extends Model implements Searchable
{
    use HasFactory, CustomKey, SoftDeletes, DefaultName, HasFactory;

    public $keyPrefix = 'soft';

    const VISIBILITY_PUBLIC = 'public';
    const VISIBILITY_PRIVATE = 'private';

    const PLATFORM_LINUX = 'Linux';
    const PLATFORM_WINDOWS = 'Windows';

    public function __construct(array $attributes = [])
    {
        $this->timestamps = true;
        $this->incrementing = false;
        $this->keyType = 'string';
        $this->connection = 'ecloud';
        $this->fillable = [
            'id',
            'name',
            'platform',
            'visibility',
            'license',
        ];
        parent::__construct($attributes);
    }

    public function scripts(): HasMany
    {
        return $this->hasMany(Script::class);
    }

    public function images()
    {
        return $this->belongsToMany(Image::class);
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
        return $query->where('visibility', Software::VISIBILITY_PUBLIC);
    }

    public function sieve(Sieve $sieve)
    {
        $sieve->configure(fn ($filter) => [
            'id' => $filter->string(),
            'name' => $filter->string(),
            'platform' => $filter->string(),
            'visibility' => $filter->boolean(),
            'license' => $filter->string(),
            'created_at' => $filter->date(),
            'updated_at' => $filter->date(),
        ]);
    }
}
