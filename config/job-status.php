<?php

return [
    'model' => App\Models\V2\ResourceTask::class,
    'event_manager' => \Imtigger\LaravelJobStatus\EventManagers\DefaultEventManager::class,
    'database_connection' => 'ecloud'
];
