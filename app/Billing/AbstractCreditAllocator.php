<?php

namespace App\Billing;

use App\Models\V1\ProductCredit;
use App\Models\V1\ProductCreditProduct;

use App\Exceptions\V1\InsufficientCreditsException;
use App\Exceptions\V1\CannotRefundProductCreditException;
use Illuminate\Support\Facades\Log;

abstract class AbstractCreditAllocator
{
    /**
     * Get remaining credit count
     * @param int    $resellerId
     * @param string $reference
     * @return int
     * @throws InsufficientCreditsException
     */
    public function getRemainingCreditsByReference(int $resellerId, string $reference): int
    {
        $creditsRemaining = ProductCredit::GetRemainingResellerCredits($resellerId, $reference);

        if (empty($creditsRemaining) || $creditsRemaining < 1) {
            throw new InsufficientCreditsException;
        }

        return $creditsRemaining;
    }

    /**
     * Assign product credits to a product reference
     * @param int    $resellerId
     * @param string $reference
     * @param int    $referenceId
     * @param int    $cost
     * @return bool
     * @throws InsufficientCreditsException
     */
    public function assignProductCreditByReference(
        int $resellerId,
        string $reference,
        int $referenceId,
        int $cost = 1
    ): bool {
        $credits = ProductCredit::AllResellerCreditsByReference($resellerId, $reference)
            ->get();

        if (empty($credits) || $credits->count() === 0) {
            throw new InsufficientCreditsException;
        }

        $selectedCreditId = 0;
        $credits->each(function ($creditItem) use (&$selectedCreditId, $cost) {
            $freeCredits = $creditItem->product_credit_amount;

            $creditItem->productCreditProducts()
                ->withActive()
                ->get()
                ->each(function ($usedCredit) use (&$freeCredits) {
                    $freeCredits -= $usedCredit->product_credit_product_credit_cost;
                    if ($freeCredits <= 0) {
                        return false;
                    }
                });

            if ($freeCredits > 0) {
                $selectedCreditId = $creditItem->product_credit_id;

                return false;
            }
        });

        if (empty($selectedCreditId)) {
            throw new InsufficientCreditsException;
        }

        $assignedCredit = (new ProductCreditProduct([
            'product_credit_product_credit_cost'  => $cost,
            'product_credit_product_credit_id'    => $selectedCreditId,
            'product_credit_product_reference_id' => $referenceId
        ]));

        return $assignedCredit->save();
    }

    /**
     * Refund product credits for a product reference
     * @param int    $resellerId
     * @param string $reference
     * @param int    $referenceId
     * @param int    $cost
     * @return bool
     * @throws CannotRefundProductCreditException
     */
    public function refundProductCreditByReference(
        int $resellerId,
        string $reference,
        int $referenceId,
        int $cost
    ): bool {
        $credits = ProductCredit::AllResellerCreditsByReference($resellerId, $reference)->get();

        $creditIds = $credits->map(function ($credit) {
            return $credit->product_credit_id;
        })->toArray();

        $assignedCredit = ProductCreditProduct::getForCreditIds($creditIds, $referenceId, $cost)->first();
        if (empty($assignedCredit)) {
            throw new CannotRefundProductCreditException;
        }

        $assignedCredit->product_credit_product_active = "No";

        try {
            return $assignedCredit->save();
        } catch (\Exception $e) {
            Log::error("Could not refund {$reference} credit: " . json_encode($assignedCredit));
            throw new CannotRefundProductCreditException;
        }
    }
}
