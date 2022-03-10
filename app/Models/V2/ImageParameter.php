<?php

namespace App\Models\V2;

use App\Traits\V2\CustomKey;
use App\Traits\V2\DefaultName;
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
class ImageParameter extends Model implements Searchable
{
    use HasFactory, CustomKey, SoftDeletes, DeletionRules, DefaultName;

    public string $keyPrefix = 'imgparam';

    const TYPE_PASSWORD = 'Password';

    public function __construct(array $attributes = [])
    {
        $this->incrementing = false;
        $this->keyType = 'string';
        $this->connection = 'ecloud';

        $this->fillable([
            'id',
            'image_id',
            'name',
            'key',
            'type',
            'description',
            'required',
            'is_hidden',
            'validation_rule',
        ]);

        $this->casts = [
            'required' => 'boolean',
            'is_hidden' => 'boolean'
        ];

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
        if (!$user->isScoped() || in_array($user->resellerId(), config('reseller.internal'))) {
            return $query;
        }

        return $query
            ->whereHas('image.vpc', function ($query) use ($user) {
                $query->where('reseller_id', $user->resellerId());
            })
            ->orWhereHas('image', function ($query) use ($user) {
                $query->where('public', true)->where('active', true);
            })
            ->where('is_hidden', false);
    }

    public function sieve(Sieve $sieve)
    {
        $sieve->configure(fn ($filter) => [
            'id' => $filter->string(),
            'image_id' => $filter->string(),
            'name' => $filter->string(),
            'key' => $filter->string(),
            'type' => $filter->string(),
            'description' => $filter->string(),
            'required' => $filter->boolean(),
            'is_hidden' => $filter->boolean(),
            'validation_rule' => $filter->string(),
            'created_at' => $filter->date(),
            'updated_at' => $filter->date(),
        ]);
    }
}
