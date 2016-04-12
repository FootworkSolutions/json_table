<?php
namespace JsonTable\Validate\Format;

use \JsonTable\Validate\AbstractFormatValidator;

/**
 * Lexical array validator.
 *
 * @package JSON table
 */
class ArrayValidator extends AbstractFormatValidator
{
    /**
     * Validate that the input is a valid null.
     *
     * @access  protected
     *
     * @return  boolean Whether the input is valid.
     */
    protected function formatDefault()
    {
        return false;
    }
}
