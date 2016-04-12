<?php
namespace JsonTable\Validate\Format;

use \JsonTable\Validate\AbstractFormatValidator;

/**
 * Lexical datetime validator.
 *
 * @package JSON table
 */
class DatetimeValidator extends AbstractFormatValidator
{
    /**
     * Validate that the input is a valid ISO8601 formatted date.
     *
     * @access  protected
     *
     * @return  boolean Whether the input is valid.
     */
    protected function formatDefault()
    {
        // Check if this is an ISO8601 datetime format (in the UTC timezone).
        if (true == preg_match('/^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2}):(\d{2})Z$/', $this->input)) {
            // Check that PHP can build a date object from the input.
            return (false !== \DateTime::createFromFormat(DATE_ISO8601, $this->input));
        }

        if ($this->formatDate('Y-m-d H:i:s')) {
            return true;
        }

        if ($this->formatDate('Y-m-d')) {
            return true;
        }

        if ($this->formatDate('H:i:s')) {
            return true;
        }

        // The input didn't match any of the expected formats.
        return false;
    }


    /**
     * Validate that the input is a valid date in the specified format.
     *
     * @access  protected
     *
     * @param   string   $format   The date format to validate against.
     *
     * @return  boolean            Whether the input is valid.
     */
    protected function formatDate($format)
    {
        $date = \DateTime::createFromFormat($format, $this->input);

        return $date && ($date->format($format) === $this->input);
    }
}
