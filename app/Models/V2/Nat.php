<?php

namespace App\Models\V2;

use App\Events\V2\Nat\Created;
use App\Events\V2\Nat\Deleted;
use App\Traits\V2\CustomKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Nat
 *
 * @property string $destination Floating IP ID
 * @property string $translated NIC ID
 * @property string $destinationable_type model type of the destination resource (See AppServiceProvider for morph maps)
 * @property string $translatedable_type model type of the translated resource (See AppServiceProvider for morph maps)
 */
class Nat extends Model
{
    use CustomKey, SoftDeletes;

    public $keyPrefix = 'nat';
    protected $keyType = 'string';
    protected $connection = 'ecloud';
    public $incrementing = false;
    public $timestamps = true;

    protected $fillable = [
        'id',
        'destination',
        'translated',
    ];

    protected $dispatchesEvents = [
        'created' => Created::class,
        'deleted' => Deleted::class
    ];

    protected $with = ['destination', 'translated'];

    /**
     * Load the associated destination resource
     * @return Model|\Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function destination()
    {
        return $this->morphTo('destinationable', null, 'destination_id', 'id');
    }

    /**
     * Load the associated translated resource
     * @return Model|\Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function translated()
    {
        return $this->morphTo('translatedable', null, 'translated_id', 'id');
    }
}
