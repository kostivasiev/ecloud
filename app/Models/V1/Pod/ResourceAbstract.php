<?php

namespace App\Models\V1\Pod;

use App\Traits\V1\UUIDHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResourceAbstract extends Model
{
    use UUIDHelper, HasFactory;

    /**
     * The connection name for the model.
     *
     * @var string
     */
    protected $connection = 'ecloud';

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var bool
     */
    public $incrementing = false;
}
