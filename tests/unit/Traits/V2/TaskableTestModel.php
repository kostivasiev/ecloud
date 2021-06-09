<?php

namespace Tests\unit\Traits\V2;

use App\Models\V2\ResellerScopeable;
use App\Traits\V2\Syncable;
use App\Traits\V2\Taskable;
use Illuminate\Database\Eloquent\Model;

class TaskableTestModel extends Model
{
    use Taskable;

    protected $fillable = [
        'id',
    ];
}
