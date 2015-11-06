<?php
namespace JsonTable\Store;

/**
 * Abstract store class.
 * All store classes should extent this class.
 *
 * @package JSON table
 */
abstract class AbstractStore extends \JsonTable\Base
{
    /**
     * @access protected
     *
     * @var array The values of the primary key of the last inserted data.
     */
    protected $_a_inserted_ids = array();


    /**
     * Get the primary keys of the records inserted in the last call to store.
     *
     * @access public
     *
     * @return array The primary keys.
     */
    public function inserted_records()
    {
        return $this->_a_inserted_ids;
    }


    /**
     * Convert a date from a specific format into ISO date format of YYYY-MM-DD.
     *
     * @access public
     * @static
     *
     * @return string The formatted date.
     */
    public static function iso_date_from_format($ps_format, $ps_date)
    {
        if (!$lo_date = \DateTime::createFromFormat($ps_format, $ps_date)) {
            throw new \Exception ("Could not reformat date $ps_date from format $ps_format");
        }

        return $lo_date->format('Y-m-d');
    }


    /**
     * Convert a value from being something that passes the FILTER_VALIDATE_BOOLEAN filter to be an actual boolean.
     *
     * @access public
     * @static
     *
     * @return boolean or null The converted value.
     */
    public static function boolean_from_filter_booleans($pm_value)
    {
        // Convert strings to lowercase as checking should be case insensitive.
        if (is_string($pm_value)) {
            $pm_value = strtolower($pm_value);
        }

        // Define the allowed true and false values.
        $la_truths = array('1', 1, true, 'on', 'yes');
        $la_false = array('0', 0, false, 'off', 'no');

        // Check if the specified value is in either the true or false arrays, if not default it to null.
        $lb_value = null;

        if (in_array($pm_value, $la_truths, true)) {
            $lb_value = true;
        } elseif (in_array($pm_value, $la_false, true)) {
            $lb_value = false;
        }

        return $lb_value;
    }
}
