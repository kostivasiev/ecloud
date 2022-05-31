<?php

namespace App\Providers;

use App\Models\V2\Dhcp;
use App\Models\V2\FloatingIp;
use App\Models\V2\FloatingIpResource;
use App\Models\V2\Instance;
use App\Models\V2\IpAddress;
use App\Models\V2\LoadBalancer;
use App\Models\V2\LoadBalancerNetwork;
use App\Models\V2\LoadBalancerNode;
use App\Models\V2\Network;
use App\Models\V2\Nic;
use App\Models\V2\OrchestratorBuild;
use App\Models\V2\Router;
use App\Models\V2\Vip;
use App\Models\V2\Volume;
use App\Models\V2\VolumeGroup;
use App\Models\V2\Vpc;
use App\Models\V2\VpnEndpoint;
use App\Models\V2\VpnProfile;
use App\Models\V2\VpnService;
use App\Models\V2\VpnSession;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\ServiceProvider;
use UKFast\Helpers\Encryption\RemoteKeyStore;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    public function boot()
    {
        $this->app->singleton('encryption_key', function () {
            if (Cache::has('encryption_key')) {
                return Cache::get('encryption_key');
            }
            $client = $this->app->makeWith(Client::class, [
                'config' => [
                    'base_uri' => config('encryption.keystore_host'),
                    'timeout' => 2,
                    'verify' => app()->environment() === 'production',
                ]
            ]);
            $key = (new RemoteKeyStore($client))->getKey(config('encryption.keystore_host_key'));
            Cache::put('encryption_key', $key, new \DateInterval('PT120S'));
            return $key;
        });

        Relation::morphMap([
            'nic' => Nic::class,
            'fip' => FloatingIp::class,
            'fipr' => FloatingIpResource::class,
            'i' => Instance::class,
            'rtr' => Router::class,
            'vol' => Volume::class,
            'vpn' => VpnService::class,
            'vpc' => Vpc::class,
            'dhcp' => Dhcp::class,
            'net' => Network::class,
            'volgroup' => VolumeGroup::class,
            'vpne' => VpnEndpoint::class,
            'obuild' => OrchestratorBuild::class,
            'vpns' => VpnSession::class,
            'vpnp' => VpnProfile::class,
            'ip' => IpAddress::class,
            'ln' => LoadBalancerNode::class,
            'lbn' => LoadBalancerNetwork::class,
            'vip' => Vip::class,
            'lb' => LoadBalancer::class,
        ]);

        Queue::exceptionOccurred(function (JobExceptionOccurred $event) {
            Log::error($event->job->getName() . " : Job exception occurred", [
                'message' => $this->formatExceptionMessage($event->exception),
                '[stacktrace]' => $event->exception->getTraceAsString(),
            ]);
        });

        Queue::failing(function (JobFailed $event) {
            Log::error(
                $event->job->getName() . " : Job failed",
                array_merge(
                    [
                        'exception' => $this->formatExceptionMessage($event->exception),
                        '[stacktrace]' => $event->exception->getTraceAsString()
                    ],
                    $this->getLoggingData($event)
                )
            );
        });

        Queue::before(function (JobProcessing $event) {
            Log::debug($event->job->resolveName() .': Started', $this->getLoggingData($event));
        });

        Queue::after(function (JobProcessed $event) {
            Log::debug($event->job->resolveName() . ': Finished', $this->getLoggingData($event));
        });
    }

    public function getLoggingData($event)
    {
        $command = unserialize($event->job->payload()['data']['command']);
        return method_exists($command, 'getLoggingData') ? $command->getLoggingData() : [];
    }

    /**
     * Adapted from GuzzleHttp\Exception\RequestException::create to create non-truncated error message
     * @param RequestException $exception
     * @return string
     */
    private function formatExceptionMessage(\Throwable $exception)
    {
        if (!($exception instanceof RequestException && $exception->hasResponse())) {
            return $exception->getMessage();
        }

        $level = (int) floor($exception->getResponse()->getStatusCode() / 100);
        if ($level === 4) {
            $label = 'Client error';
        } elseif ($level === 5) {
            $label = 'Server error';
        } else {
            $label = 'Unsuccessful request';
        }

        $message = sprintf(
            '%s: `%s %s` resulted in a `%s %s` response',
            $label,
            $exception->getRequest()->getMethod(),
            $exception->getRequest()->getUri(),
            $exception->getResponse()->getStatusCode(),
            $exception->getResponse()->getReasonPhrase()
        );

        if (!is_null($exception->getResponse())) {
            $stream = $exception->getResponse()->getBody();
            $stream->rewind();
            $message .= ': ' . $stream->getContents();
        }

        return $message;
    }
}
