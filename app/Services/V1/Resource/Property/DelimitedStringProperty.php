<?php

namespace App\Services\V1\Resource\Property;

use App\Services\V1\Resource\Exceptions\InvalidPropertyException;

class DelimitedStringProperty extends AbstractProperty
{
    /**
     * The separator used in the serialization processes
     * @var string
     */
    protected $separator;

    /**
     * StringProperty constructor.
     * @param string $databaseName
     * @param string $friendlyName
     * @param string $separator
     * @param $value
     */
    public function __construct($databaseName, $friendlyName, $value = null, $separator = ',')
    {
        parent::__construct($databaseName, $friendlyName, $value);

        $this->separator = $separator;
    }

    /**
     * Returns a friendly formatted property
     * @throws InvalidPropertyException
     *
     * @return array
     */
    public function serialize()
    {
        if (empty($this->value)) {
            return [];
        }

        $value = explode($this->separator, $this->value);

        if ($value === false) {
            throw new InvalidPropertyException('Invalid String delimiter: "' . $this->separator . '"');
        }

        return $value;
    }

    /**
     * Returns a database formatted property
     *
     * @return null|string
     */
    public function deserialize()
    {
        if (empty($this->value)) {
            return $this->value;
        }

        return implode($this->separator, $this->value);
    }

    /**
     * Statically create an instance of itself
     * @param $databaseName
     * @param $friendlyName
     * @param $value
     * @param string $separator
     *
     * @return static
     */
    public static function create($databaseName, $friendlyName, $value = null, $separator = ',')
    {
        return new static($databaseName, $friendlyName, $value, $separator);
    }
}
