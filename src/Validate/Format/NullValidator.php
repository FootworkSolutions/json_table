<?php
namespace JsonTable\Validate\Format;

use \JsonTable\Validate\AbstractFormatValidator;

/**
 * Lexical boolean validator.
 *
 * @package JSON table
 */
class NullValidator extends AbstractFormatValidator
{
    /**
     * Validate that the input is a valid null.
     * This actually has to check that the value is an empty string as all inputs are received as strings.
     *
     * @access protected
     *
     * @return boolean Whether the input is valid.
     */
    protected function formatDefault()
    {
        return ('' === $this->input || '\N' === $this->input);
    }
}
