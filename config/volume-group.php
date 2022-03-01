<?php

return [
    'scsi_controller_reserved_port' => env('VOLUME_GROUP_SCSI_CONTROLLER_RESERVED_PORT', 7),
    'max_ports' => env('VOLUME_GROUP_MAX_PORTS', 10),
    'max_instances' => env('VOLUME_GROUP_MAX_INSTANCES', 2),
];
