<?php

namespace Tests\Unit\Traits\V2;

use App\Traits\V2\Taskable;
use Illuminate\Database\Eloquent\Model;

class TaskableTestModel extends Model
{
    use Taskable;

    protected $fillable = [
        'id',
    ];
}
