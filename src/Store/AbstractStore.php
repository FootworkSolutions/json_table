<?php
namespace JsonTable\Store;

use \JsonTable\Base;
/**
 * Abstract store class.
 * All store classes should extent this class.
 *
 * @package JSON table
 */
abstract class AbstractStore extends Base
{
    /**
     * @var array The values of the primary key of the last inserted data.
     */
    protected $insertedIds = [];


    /**
     * Get the primary keys of the records inserted in the last call to store.
     *
     * @return array The primary keys.
     */
    public function insertedRecords()
    {
        return $this->insertedIds;
    }


    /**
     * Convert a date from a specific format into ISO date format of YYYY-MM-DD.
     *
     * @static
     *
     * @param   string  $format           The format to convert from.
     * @param   string  $dateToConvert    The date to convert.
     *
     * @return  string  The formatted date.
     *
     * @throws  \Exception is the date couldn't be reformatted.
     */
    public static function isoDateFromFormat($format, $dateToConvert)
    {
        if (!$date = \DateTime::createFromFormat($format, $dateToConvert)) {
            throw new \Exception("Could not reformat date $dateToConvert from format $format");
        }

        return $date->format('Y-m-d');
    }


    /**
     * Convert a value from being something that passes the FILTER_VALIDATE_BOOLEAN filter to be an actual boolean.
     *
     * @static
     *
     * @param   mixed   $value  The value to convert to a boolean.
     *
     * @return  boolean|null The converted value.
     */
    public static function booleanFromFilterBooleans($value)
    {
        if (is_string($value)) {
            $value = strtolower($value);
        }

        $truths = ['1', 1, true, 'on', 'yes', 'true'];
        $false = ['0', 0, false, 'off', 'no', 'false'];

        if (in_array($value, $truths, true)) {
            return true;
        }

        if (in_array($value, $false, true)) {
            return false;
        }

        return null;
    }
}
