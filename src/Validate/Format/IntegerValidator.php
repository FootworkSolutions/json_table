<?php
namespace JsonTable\Validate\Format;

use \JsonTable\Validate\AbstractFormatValidator;

/**
 * Lexical integer validator.
 *
 * @package JSON table
 */
class IntegerValidator extends AbstractFormatValidator
{
    /**
     * Validate that the input is a valid integer.
     *
     * @return  boolean Whether the input is valid.
     */
    protected function formatDefault()
    {
        return (false !== filter_var($this->input, FILTER_VALIDATE_INT));
    }
}
