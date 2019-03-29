<?php

namespace App\Listeners\V1;

use App\Events\V1\EncryptionEnabledOnSolutionEvent;
use Illuminate\Http\Request;
use Log;

/**
 * Class EncryptionEnabledOnSolutionListener
 * @package App\Listeners\V1
 */
class EncryptionEnabledOnSolutionListener
{
    public $request;

    /**
     * Create the event listener.
     *
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function handle(EncryptionEnabledOnSolutionEvent $event)
    {
        // Log the appliance deletion
        Log::info(
            'Virtual machine encryption was enabled on solution',
            [
                'id' => $event->solution->getKey(),
                'reseller_id' => $this->request->user->resellerId
            ]
        );


//        //  Send email to the backup team to let them know, as backup changes are required
//
//        $to = "paul.mcnally@ukfast.co.uk";
//        $subject = 'Virtual machine encryption enabled on eCloud solution';
//
//        $content = 'Virtual machine encryption enabled on eCloud solution # ' . $event->solution->getKey();
//
//        $headers = "From: alerts@ukfast.co.uk.co.uk" . "\r\n";
//
//        mail($to,$subject,$content,$headers);
    }
}
