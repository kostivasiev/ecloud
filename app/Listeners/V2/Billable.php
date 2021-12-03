<?php
namespace App\Listeners\V2;

interface Billable
{
    /**
     * Gets the friendly name for the billing metric
     * @return string
     */
    public static function getFriendlyName(): string;

    /**
     * Gets the billing metric key
     * @return string
     */
    public static function getKeyName(): string;
}
