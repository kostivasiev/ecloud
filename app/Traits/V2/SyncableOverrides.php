<?php

namespace App\Traits\V2;

use App\Models\V2\FirewallPolicy;
use App\Models\V2\Host;
use App\Models\V2\HostGroup;
use App\Models\V2\NetworkPolicy;
use App\Models\V2\Sync;
use Illuminate\Support\Facades\Log;

trait SyncableOverrides
{
    public function delete()
    {
        $class = explode('\\', __CLASS__);
        $class = 'App\\Jobs\\Sync\\' . end($class) . '\\Delete';
        if (!class_exists($class)) {
            throw new \Exception('Syncable "Delete" job not found for ' . __CLASS__);
        }

        if (!$this->createSync(Sync::TYPE_DELETE)) {
            return false;
        }

        dispatch(new $class($this));

        return true;
    }

    public function syncDelete()
    {
        $response = parent::delete();
        if (!$response) {
            Log::error(get_class($this) . ' : Failed to delete', ['resource_id' => $this->id]);
            return $response;
        }
        Log::info(get_class($this) . ' : Deleted', ['resource_id' => $this->id]);
    }

    public function save(array $options = [])
    {
        if (!in_array(__CLASS__, [
            FirewallPolicy::class,
            NetworkPolicy::class,
            HostGroup::class,
            Host::class
        ])) {
            return parent::save($options);
        }

        $originalValues = $this->getOriginal();
        $response = parent::save($options);
        if (!$response) {
            Log::error(get_class($this) . ' : Failed to save', ['resource_id' => $this->id]);
            return $response;
        }

        $class = explode('\\', __CLASS__);
        $class = 'App\\Jobs\\Sync\\' . end($class) . '\\Save';
        if (!class_exists($class)) {
            throw new \Exception('Syncable "Save" job not found for ' . __CLASS__);
        }

        if (!$this->createSync(Sync::TYPE_UPDATE)) {
            return false;
        }

        dispatch(new $class($this, $originalValues));

        return $response;
    }
}
