<?php

namespace App\Events\V1;

use App\Events\Event;
use App\Models\V1\Datastore;

/**
 * Class DatastoreExpandEvent
 *
 * An event to be triggered on expanding a datastore.
 *
 * @package App\Events\V1
 */
class DatastoreExpandEvent extends Event
{
    public $datastore;

    public $newSizeGb;

    /**
     * DatastoreExpandEvent constructor.
     * @param Datastore $datastore
     * @param $newSizeGb
     */
    public function __construct(Datastore $datastore, $newSizeGb)
    {
        $this->datastore = $datastore;

        $this->newSizeGb = $newSizeGb;
    }
}
