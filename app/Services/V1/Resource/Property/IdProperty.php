<?php
namespace App\Services\V1\Resource\Property;

use App\Services\V1\Resource\Exceptions\InvalidPropertyException;

class IdProperty extends AbstractProperty
{
    /**
     * Array of valid ID types
     * @var array
     */
    protected $validTypes = [
        "numeric",
        "uuid"
    ];

    /**
     * The type of id this property represents
     * @var string
     */
    protected $type;

    /**
     * IdProperty constructor.
     * @param $databaseName
     * @param $friendlyName
     * @param $value
     * @param string $type
     * @throws InvalidPropertyException
     */
    public function __construct($databaseName, $friendlyName, $value = null, $type = 'numeric')
    {
        if (!in_array($type, $this->validTypes)) {
            throw new InvalidPropertyException('Invalid id property type: "' . $type . '"');
        }
        
        parent::__construct($databaseName, $friendlyName, $value);
        $this->type = $type;
    }

    /**
     * Returns a friendly formatted property
     * @return null|int|string
     */
    public function serialize()
    {
        return $this->sanitize();
    }

    /**
     * Returns a database formatted property
     * @return null|int|string
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

        switch ($this->type) {
            case 'numeric':
                $this->value = intval($this->value);
                break;

            case 'uuid':
                $this->value = strval($this->value);
                break;

            default:
                break;
        }
        return $this->value;
    }

    /**
     * Statically create an instance of itself
     * @param $databaseName
     * @param $friendlyName
     * @param $value
     * @param string $type
     * @return static
     * @throws InvalidPropertyException
     */
    public static function create($databaseName, $friendlyName, $value = null, $type = 'numeric')
    {
        return new static($databaseName, $friendlyName, $value, $type);
    }
}
