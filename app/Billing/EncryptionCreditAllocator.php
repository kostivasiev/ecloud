<?php

namespace App\Billing;

class EncryptionCreditAllocator extends AbstractCreditAllocator implements CreditAllocatorInterface
{
    /** Shortcut for getting VM Encryption credits
     * @param int $resellerId
     * @return int
     * @throws \App\Exceptions\V1\InsufficientCreditsException
     */
    public function getRemainingCredits(int $resellerId): int
    {
        return parent::getRemainingCreditsByReference($resellerId, "ecloud_vm_encryption");
    }

    /**
     * Shortcut to assign VM Encryption credit
     * @param int $resellerId
     * @param int $serverId
     * @return bool
     * @throws \App\Exceptions\V1\InsufficientCreditsException
     */
    public function assignCredit(int $resellerId, int $serverId): bool
    {
        return parent::assignProductCreditByReference($resellerId, "ecloud_vm_encryption", $serverId, 1);
    }

    /**
     * Shortcut to refund VM Encryption credit
     * @param int $resellerId
     * @param int $serverId
     * @return bool
     * @throws \App\Exceptions\V1\CannotRefundProductCreditException
     */
    public function refundCredit(int $resellerId, int $serverId): bool
    {
        return parent::refundProductCreditByReference($resellerId, "ecloud_vm_encryption", $serverId, 1);
    }
}
