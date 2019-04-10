<?php

namespace App\Billing;

interface CreditAllocatorInterface
{
    /** Get remaining credits
     * @param int $resellerId
     * @return int
     * @throws \App\Exceptions\V1\InsufficientCreditsException
     */
    public function getRemainingCredits(int $resellerId): int;

    /**
     * Assign a credit
     * @param int $resellerId
     * @param int $domainId
     * @return bool
     * @throws \App\Exceptions\V1\InsufficientCreditsException
     */
    public function assignCredit(int $resellerId, int $domainId): bool;

    /**
     * Refund a credit
     * @param int $resellerId
     * @param int $domainId
     * @return bool
     * @throws \App\Exceptions\V1\CannotRefundProductCreditException
     */
    public function refundCredit(int $resellerId, int $domainId): bool;
}
