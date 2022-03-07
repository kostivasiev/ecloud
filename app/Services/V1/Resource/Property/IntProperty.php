<?php
namespace App\Services\V1\Resource\Property;

class IntProperty extends AbstractProperty
{
    /**
     * IntProperty constructor.
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
     * @return null|int
     */
    public function serialize()
    {
        if (is_null($this->value)) {
            return $this->value;
        }
        $this->value = intval($this->value);
        return $this->value;
    }

    /**
     * Returns a database formatted property
     * @return null|int
     */
    public function deserialize()
    {
        if (is_null($this->value)) {
            return $this->value;
        }
        $this->value = intval($this->value);
        return $this->value;
    }

    /**
     * Sanitizes a value
     * @return int|mixed|string
     */
    protected function sanitize()
    {
        if (is_null($this->value)) {
            return $this->value;
        }
        $this->value = intval($this->value);
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
