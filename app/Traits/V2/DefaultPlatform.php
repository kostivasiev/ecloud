<?php
namespace App\Traits\V2;

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
    }

    public function setDefaultPlatform()
    {
        if (empty($this->platform)) {
            try {
                $this->platform = $this->applianceVersion->serverLicense()->category;
                $this->save();
            } catch (\Exception $e) {
                // There is no platform, do nothing
            }
        }
    }
}
