<?php

return [
    'model' => App\Models\V2\TaskJobStatus::class,
    'event_manager' => App\Events\TaskEventManager::class,
    'database_connection' => 'ecloud'
];
