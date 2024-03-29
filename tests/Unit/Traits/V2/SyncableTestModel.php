<?php

namespace Tests\Unit\Traits\V2;

use App\Traits\V2\Syncable;
use Illuminate\Database\Eloquent\Model;

class SyncableTestModel extends Model
{
    use Syncable;

    protected $fillable = [
        'id',
    ];

    public function save(array $options = [])
    {
        return true;
    }

    public function getUpdateSyncJob()
    {
        return 'App\\Jobs\\Sync\\SyncableTestModel\\Update';
    }

    public function getDeleteSyncJob()
    {
        return 'App\\Jobs\\Sync\\SyncableTestModel\\Delete';
    }
}
