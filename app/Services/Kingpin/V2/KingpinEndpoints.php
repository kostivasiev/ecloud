<?php

namespace App\Services\Kingpin\V2;

/**
 * A place to store endpoints for kingpin.
 *
 * Links referred to from swagger docs;
 */
class KingpinEndpoints
{
    public const GET_CONSOLE_SCREENSHOT = '/api/v2/vpc/%s/instance/%s/screenshot';
    public const GET_CONSOLE_SESSION = '/api/v2/vpc/%s/instance/%s/console/session';
}
