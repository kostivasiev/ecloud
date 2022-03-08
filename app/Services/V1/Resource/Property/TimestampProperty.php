<?php
namespace App\Services\V1\Resource\Property;

use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Support\Carbon;

class TimestampProperty extends AbstractProperty
{
    /**
     * TimestampProperty constructor.
     * @param $databaseName
     * @param $friendlyName
     * @param $value
     */
    public function __construct($databaseName, $friendlyName, $value = null)
    {
        parent::__construct($databaseName, $friendlyName, $value);
    }

    /**
     * Returns a friendly formatted property
     * @return \DateTime
     * @return mixed
     */
    public function serialize()
    {
        if (is_null($this->value)) {
            return $this->value;
        }
        try {
            $this->value = Carbon::parse(
                $this->value,
                new \DateTimeZone(config('app.timezone'))
            )->toIso8601String();
        } catch (InvalidFormatException) {
            $this->value = null;
        }
        return $this->value;
    }

    /**
     * Returns A database formatted property
     */
    public function deserialize()
    {
        try {
            $this->value = Carbon::parse(
                $this->value,
                new \DateTimeZone(config('app.timezone'))
            )->toAtomString();
        } catch (InvalidFormatException) {
            $this->value = null;
        }
        return $this->value;
    }

    /**
     * Statically create an instance of itself
     * @param $databaseName
     * @param $friendlyName
     * @param $value
     * @return static
     */
    public static function create($databaseName, $friendlyName, $value = null)
    {
        return new static($databaseName, $friendlyName, $value);
    }
}
