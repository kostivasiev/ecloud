<?php

namespace App\Listeners\V1;

use App\Events\V1\EncryptionEnabledOnSolutionEvent;
use App\Exceptions\V1\IntapiServiceException;
use App\Exceptions\V1\ServiceResponseException;
use App\Exceptions\V1\ServiceUnavailableException;
use App\Services\IntapiService;
use Illuminate\Http\Request;
use Log;

/**
 * Class EncryptionEnabledOnSolutionListener
 * @package App\Listeners\V1
 */
class EncryptionEnabledOnSolutionListener
{
    public $request;

    public $intapiService;

    /**
     * Create the event listener.
     *
     * @param Request $request
     * @param IntapiService $intapiService
     */
    public function __construct(Request $request, IntapiService $intapiService)
    {
        $this->request = $request;
        $this->intapiService = $intapiService;
    }

    public function handle(EncryptionEnabledOnSolutionEvent $event)
    {
        // Fire off automation request
        try {
            $this->intapiService->automationRequest(
                'solution_encryption_enable',
                'ucs_reseller',
                $event->solution->getKey(),
                [],
                'ecloud_ucs_' . $event->solution->pod->getKey(),
                $this->request->user()->userId(),
                $this->request->user()->type()
            );

            $intapiData = $this->intapiService->getResponseData();
        } catch (IntapiServiceException $exception) {
            $this->resetSolutionEncryptionFlag($event->solution);

            throw new ServiceUnavailableException('Unable to schedule solution changes');
        }

        if (!$intapiData->result) {
            $errorMessage = is_array($intapiData->errorset->error) ?
                end($intapiData->errorset->error) :
                $intapiData->errorset->error;

            $this->resetSolutionEncryptionFlag($event->solution);

            throw new ServiceResponseException($errorMessage);
        }

        Log::info(
            'Virtual machine encryption was enabled on Solution',
            [
                'id' => $event->solution->getKey(),
                'reseller_id' => $this->request->user()->resellerId()
            ]
        );
    }

    private function resetSolutionEncryptionFlag($solution)
    {
        $solution->ucs_reseller_encryption_enabled = 'No';
        $solution->save();
    }
}
