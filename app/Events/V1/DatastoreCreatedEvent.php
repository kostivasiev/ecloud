<?php

namespace App\Events\V1;

use App\Events\Event;
use App\Models\V1\Datastore;

/**
 * Class DatastoreCreatedEvent
 *
 * An event to be triggered on deleting Appliance records.
 *
 * @package App\Events\V1
 */
class DatastoreCreatedEvent extends Event
{
    public $datastore;

    /**
     * ApplianceDeletedEvent constructor.
     * @param Datastore $datastore
     */
    public function __construct(Datastore $datastore)
    {
        $this->datastore = $datastore;
    }
}
