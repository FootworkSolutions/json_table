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
     * @return  boolean Whether the input is valid.
     */
    protected function formatDefault()
    {
        if (1 === preg_match('/^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2}):(\d{2})Z$/', $this->input)) {
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

        return false;
    }


    /**
     * Validate that the input is a valid date in the specified format.
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
