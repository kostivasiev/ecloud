<?php

namespace App\Models\V2;

use App\Events\V2\NatCreated;
use App\Traits\V2\CustomKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Nat
 *
 * @property string $destination Floating IP ID
 * @property string $translated NIC ID
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
        'created' => NatCreated::class,
    ];

    /**
     * Load the associated destination resource
     * @return Model|\Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function destination()
    {
        return $this->morphTo('destinationable', null, 'destination', 'id')->firstOrFail();
    }

    /**
     * Load the associated translated resource
     * @return Model|\Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function translated()
    {
        return $this->morphTo('translatedable', null, 'translated', 'id')->firstOrFail();
    }
}
