<?php

return [
    'model' => App\Models\V2\Task::class,
    'event_manager' => App\Events\TaskEventManager::class,
    'database_connection' => 'ecloud'
];
