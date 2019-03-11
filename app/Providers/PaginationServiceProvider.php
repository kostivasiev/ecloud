<?php

namespace App\Providers;

use GuzzleHttp\Psr7\Uri;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class PaginationServiceProvider extends ServiceProvider
{
    public function register()
    {
        Paginator::currentPathResolver(function () {
            $uri = new Uri($this->app->request->url());

            $customUriString = env('APP_URL', false);
            if ($customUriString != false) {
                $customUri = new Uri($customUriString);
                $newPath = rtrim($customUri->getPath(), '/')
                    . "/"
                    . ltrim($uri->getPath(), "/");

                $uri = $customUri->withPath($newPath);
            }

            return $uri;
        });
    }
}
