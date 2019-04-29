<?php

namespace App\VM;

use ReflectionClass;

class Status
{
    /**
     * VM has finished all changes
     */
    const COMPLETE = 'Complete';

    /**
     * VM has begun setup process
     */
    const SETUP_STARTED = 'Server Setup Started';

    /**
     * VM is midway through setup process
     */
    const INCOMPLETE = 'Incomplete';

    /**
     * Unkown state
     */
    const UNKNOWN = 'Unknown';

    /**
     * VM is being tested
     */
    const TESTING = 'Testing';

    /**
     * VM IP is unresponsive
     */
    const UNRESPONSIVE_IP = 'Unresponsive IP Address';

    /**
     *
     */
    const PARTIAL_IP = 'Partial IP Information';

    /**
     * Failed to connect to UKFast Backup
     */
    const UKFB_CONNECTION_ERROR = 'Failed to connect to UKFB';

    /**
     *
     */
    const NEW_CLIENT_REGISTERED = 'New client registered';

    /**
     *
     */
    const OLD = 'Old Server';

    /**
     *
     */
    const IN_STOCK = 'In Stock';

    /**
     *
     */
    const OS_INSTALLED = 'OS Installed';

    /**
     * VM changes were cancelled
     */
    const CANCELLED = 'Cancelled';

    /**
     *
     */
    const ORDER_SUBMITTED = 'Order Submitted';

    /**
     *
     */
    const AWAITING_INSTALLATION = 'Awaiting Installation';

    /**
     *
     */
    const INCORRECT_VLAN = 'Incorrect VLAN';

    /**
     *
     */
    const BEING_BUILT = 'Being Built';

    /**
     *
     */
    const DEPLOYED = 'Deployed awaiting assignment';

    /**
     *
     */
    const AWAITING_WINDOWS_INSTALL = 'Awaiting Windows Installation';

    /**
     *
     */
    const UKFB_INIT_ERROR = 'Failed to initialise UKFast Backup connection';

    /**
     * Failed to reboot VM
     */
    const REBOOT_FAILED = 'Reboot Failed';

    /**
     *
     */
    const HARDWARE_UPGRADE = 'Awaiting Hardware Upgrade';

    /**
     *
     */
    const MISSING_VLAN = 'Missing VLAN';

    /**
     *
     */
    const CLONING_TO_TEMPLATE = 'Cloning To Template';

    /**
     * The VM is currently being encrypted
     */
    const ENCRYPTING = 'Encrypting';

    /**
     * The VM is currently being decrypted
     */
    const DECRYPTING = 'Decrypting';

    /**
     *
     */
    const RESIZING = 'Resizing';

    /**
     * Return class constants
     * @return array
     * @throws \ReflectionException
     */
    public static function all()
    {
        return (new ReflectionClass(static::class))->getConstants();
    }
}
