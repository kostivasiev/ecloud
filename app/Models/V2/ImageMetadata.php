<?php

namespace App\Models\V2;

use App\Traits\V2\CustomKey;
use App\Traits\V2\DeletionRules;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use UKFast\Api\Auth\Consumer;
use UKFast\Sieve\Searchable;
use UKFast\Sieve\Sieve;

/**
 * Class ImageParameter
 * @package App\Models\V2
 */
class ImageMetadata extends Model implements Searchable
{
    use HasFactory, CustomKey, SoftDeletes, DeletionRules;

    public string $keyPrefix = 'imgmeta';

    protected $casts = [
        'required' => 'boolean',
    ];

    public function __construct(array $attributes = [])
    {
        $this->incrementing = false;
        $this->keyType = 'string';
        $this->connection = 'ecloud';

        $this->fillable([
            'id',
            'image_id',
            'key',
            'value',
        ]);
        parent::__construct($attributes);
    }

    public function image()
    {
        return $this->belongsTo(Image::class);
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

        return $query->whereHas('image', function ($query) use ($user) {
            $query->where('reseller_id', $user->resellerId());
        })
            ->orWhereHas('image', function ($query) use ($user) {
                $query->where('public', true)->where('active', true);
            });
    }

    public function sieve(Sieve $sieve)
    {
        $sieve->configure(fn ($filter) => [
            'id' => $filter->string(),
            'image_id' => $filter->string(),
            'key' => $filter->string(),
            'value' => $filter->string(),
            'created_at' => $filter->date(),
            'updated_at' => $filter->date(),
        ]);
    }
}
