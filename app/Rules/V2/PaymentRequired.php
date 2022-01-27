<?php

namespace App\Rules\V2;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use UKFast\Admin\Account\AdminClient;

/**
 * Class IsSubnetBigEnough
 * @package App\Rules\V2
 */
class PaymentRequired implements Rule
{
    public function passes($attribute, $value)
    {
        $user = Auth::user();
        if ($user->isScoped()) {
            $accountAdminClient = app()->make(AdminClient::class);
            try {
                $customer = $accountAdminClient->customers()->getById($user->resellerId());
            } catch (\Exception $e) {
                if ($e->getResponse()->getStatusCode() !== 404) {
                    Log::info($e);
                    return false;
                }
                return false;
            }

            if ($customer->paymentMethod == 'Credit Card') {
                return false;
            }
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function message()
    {
        return 'Customer not found or payment required.';
    }
}
