<?php

namespace App\Providers\V1;

use App\Services\V1\Resource\Resource;
use GuzzleHttp\Psr7\Uri;
use Illuminate\Support\ServiceProvider;
use function env;

class ResourceServiceProvider extends ServiceProvider
{
    public function register()
    {
        Resource::setCurrentPathResolver(function ($request) {
            $uri = new Uri($request->url());

            $customUriString = env('APP_URL', false);
            if ($customUriString != false) {
                $customUri = new Uri($customUriString);
                $newPath = rtrim($customUri->getPath(), '/')
                    . "/"
                    . ltrim($uri->getPath(), "/");

                $uri = $customUri->withPath($newPath);
            }

            return $uri->__toString();
        });
    }
}
