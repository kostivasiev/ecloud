<?php
namespace App\Traits\V2\Jobs;

use App\Models\V2\Instance;
use App\Models\V2\Script;
use Illuminate\Support\Facades\Log;

trait RunsScripts
{
    public $tries = 120;

    public $backoff = 30;

    protected function runScript(Instance $instance, $script): bool
    {
        $code = ($script instanceof Script) ? $script->script : $script;

        $logData = ['id' => $instance->id,];

        if ($script instanceof Script) {
            $logData['script_id'] = $script->id;
        }

        $guestAdminCredential = $instance->credentials()
            ->where('username', ($instance->platform == 'Linux') ? 'root' : 'graphite.rack')
            ->firstOrFail();

        if (!$guestAdminCredential) {
            $message = 'Failed to load guest admin credentials for ' . $instance->id;
            Log::error(get_class($this) . ': ' . $message, $logData);
            $this->fail(new \Exception($message));
            return false;
        }

        $response = $instance->availabilityZone->kingpinService()->post(
            '/api/v2/vpc/' . $instance->vpc->id .
            '/instance/' . $instance->id .
            '/guest/' . strtolower($instance->platform) .
            '/script',
            [
                'json' => [
                    'encodedScript' => base64_encode($code),
                    'username' => $guestAdminCredential->username,
                    'password' => $guestAdminCredential->password,
                ],
            ]
        );

        $response = json_decode($response->getBody()->getContents());
        if (!$response) {
            $message = 'Could not decode response from script for instance ' . $instance->id;
            Log::error(get_class($this) . ': ' . $message, $logData);
            throw new \Exception($message);
        }

        // 0 = completed, 1 = error, any other = retry
        switch ($response->exitCode) {
            case 0:
                Log::info(get_class($this) . ': Script for instance ' .  $instance->id . ' completed successfully', $logData);
                return true;
            case 1:
                $message = 'Script for instance ' .  $instance->id . ' failed';
                $logData['output'] = $response->output;
                Log::error(get_class($this) . ': ' . $message, $logData);
                $this->fail(new \Exception($message . '. Output: ' . $response->output));
                return false;
            default:
                Log::info(get_class($this) . ': Script for instance ' .  $instance->id . ' not yet complete, retrying in ' . $this->backoff . ' seconds', $logData);
                $this->release($this->backoff);
                return false;
        }
    }
}
