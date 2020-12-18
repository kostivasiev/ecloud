<?php

namespace App\Mail;

use App\Models\V2\AvailabilityZoneCapacity;
use Illuminate\Mail\Mailable;

class AvailabilityZoneCapacityAlert extends Mailable
{
    public AvailabilityZoneCapacity $availabilityZoneCapacity;

    const ALERT_LEVEL_NOTICE = 'NOTICE';
    const ALERT_LEVEL_WARNING = 'WARNING';
    const ALERT_LEVEL_CRITICAL = 'CRITICAL';

    public string $alertLevel = self::ALERT_LEVEL_NOTICE;

    public int $priority = 3;

    public function __construct($availabilityZoneCapacity)
    {
        $this->availabilityZoneCapacity = $availabilityZoneCapacity;

        if ($this->availabilityZoneCapacity->current >= $this->availabilityZoneCapacity->alert_warning) {
            $this->alertLevel = self::ALERT_LEVEL_WARNING;
            $this->priority = 2;
        }

        if ($this->availabilityZoneCapacity->current >= $this->availabilityZoneCapacity->alert_critical) {
            $this->alertLevel = self::ALERT_LEVEL_CRITICAL;
            $this->priority = 1;
        }
    }

    /**
     * @return AvailabilityZoneCapacityAlert
     */
    public function build()
    {
        $this->from(config('alerts.from'));

        if ($this->availabilityZoneCapacity->availability_zone_id == 'az-aaaaaaaa') { // Email to DEV if using the testing AZ
            $this->to(config('alerts.capacity.dev.to'));
        } else {
            if (config()->has('alerts.capacity.' . $this->availabilityZoneCapacity->type)) {
                $this->to(config('alerts.capacity.' . $this->availabilityZoneCapacity->type . '.to'));

                if (config()->has('alerts.capacity.' . $this->availabilityZoneCapacity->type . '.cc')) {
                    $this->cc(config('alerts.capacity.' . $this->availabilityZoneCapacity->type . '.cc'));
                }
            } else {
                $this->to(config('alerts.capacity.default.to'));
            }
        }

        $this->subject($this->alertLevel . ': Low ' . $this->availabilityZoneCapacity->type . ' Alert For Availability Zone ' . $this->availabilityZoneCapacity->availability_zone_id);

        $this->priority($this->priority);

        return $this->view('mail.capacityAlert')
            ->with([
                'availability_zone_id' => $this->availabilityZoneCapacity->availability_zone_id,
                'availability_zone_name' => $this->availabilityZoneCapacity->availabilityZone->name,
                'availability_zone_code' => $this->availabilityZoneCapacity->availabilityZone->code,
                'type' => $this->availabilityZoneCapacity->type,
                'capacity' => $this->availabilityZoneCapacity->current,
                'alert_level' => $this->alertLevel
            ]);
    }
}
