<?php

namespace App\Providers;

use DateInterval;
use GuzzleHttp\Client;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\ServiceProvider;
use UKFast\Helpers\Encryption\RemoteKeyStore;

class EncryptionServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('encrypter', function () {
            return new Encrypter($this->registerEncryptionKey(), config('app.cipher'));
        });
    }

    /**
     * Binds a new encryption key to the service container in the format
     * encryption-key
     * Also stores the key in redis for 2 minutes to enable faster retrieval
     * in-between requests.
     *
     */
    protected function registerEncryptionKey()
    {
        if (Cache::has(md5('encryption-key'))) {
            return Cache::get(md5('encryption-key'));
        }

        $client = new Client([
            'base_uri' => config('encryption.keystore_host'),
            'timeout' => 2,
            'verify' => app()->environment('production'),
        ]);

        $key = (new RemoteKeyStore($client))
            ->getKey(config('encryption.keystore_host_key'));

        Cache::put(md5('encryption-key'), $key, new DateInterval('PT120S'));

        return $key;
    }
}
