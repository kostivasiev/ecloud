<?php

return [
    'model' => App\Models\V2\ResourceTaskStatus::class,
    'event_manager' => \Imtigger\LaravelJobStatus\EventManagers\DefaultEventManager::class,
    'database_connection' => 'ecloud'
];
