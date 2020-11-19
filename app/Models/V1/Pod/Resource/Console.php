<?php

namespace App\Models\V1\Pod\Resource;

use App\Models\V1\Pod\ResourceAbstract;
use App\Traits\V1\Encryption;

class Console extends ResourceAbstract
{
    use Encryption;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'pod_resource_console';

    /**
     * @var array
     */
    protected $fillable = [
        'token',
        'url',
        'console_url',
    ];

    public function setTokenAttribute($value)
    {
        $this->attributes['token'] = (empty($value)) ? '' : $this->encryption()->encrypt($value);
    }

    public function getTokenAttribute($value)
    {
        if (empty($value)) {
            return '';
        }
        return $this->encryption()->decrypt($value);
    }
}
