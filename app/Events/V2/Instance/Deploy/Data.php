<?php

namespace App\Events\V2\Instance\Deploy;

class Data
{
    public $instance_id;
    public $vpc_id;
    public $volume_capacity;
    public $network_id;
    public $floating_ip_id;
    public $requires_floating_ip;
    public $appliance_data;
    public $user_script;
    public $iops;
}
