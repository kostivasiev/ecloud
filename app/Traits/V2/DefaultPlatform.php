<?php
namespace App\Traits\V2;

use Illuminate\Support\Facades\Log;

trait DefaultPlatform
{
    /**
     * @throws \Exception
     */
    public static function initializeDefaultPlatform()
    {
        static::created(function ($instance) {
            $instance->setDefaultPlatform();
        });

        static::updated(function ($instance) {
            $instance->setDefaultPlatform();
        });
    }

    public function setDefaultPlatform()
    {
        if (empty($this->platform) && $this->applianceVersion) {
            try {
                $this->platform = $this->applianceVersion->serverLicense()->category;
                $this->save();
            } catch (\Exception $e) {
                // There is no platform, do nothing
                Log::info("No platform found: " . $e->getMessage());
            }
        }
    }
}
