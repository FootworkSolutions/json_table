<?php
namespace JsonTable\Validate\Format;

use \JsonTable\Validate\AbstractFormatValidator;

/**
 * Lexical boolean validator.
 *
 * @package JSON table
 */
class BooleanValidator extends AbstractFormatValidator
{
    /**
     * Validate that the input is a valid boolean.
     * This accepts:
     * 1 and 0
     * "1" and "0"
     * "on" or "ON" and "off" or "OFF"
     * "yes" or "YES" and "no" or "NO"
     * true and false
     *
     * @access  protected
     *
     * @return  boolean Whether the input is valid.
     */
    protected function formatDefault()
    {
        $value = (is_string($this->input)) ? strtolower($this->input) : $this->input;

        return (null !== filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE));
    }
}
