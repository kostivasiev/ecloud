<?php
namespace App\Services\V1\Resource\Property;

class StringProperty extends AbstractProperty
{
    /**
     * StringProperty constructor.
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
     * @return null|string
     */
    public function serialize()
    {
        return $this->sanitize();
    }

    /**
     * Returns a database formatted property
     * @return null|string
     */
    public function deserialize()
    {
        return $this->sanitize();
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
        $this->value = strval($this->value);
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
