<?php
namespace JsonTable\Validate\Format;

use \JsonTable\Validate\AbstractFormatValidator;

/**
 * Lexical any validator.
 *
 * @package JSON table
 */
class AnyValidator extends AbstractFormatValidator
{
    /**
     * Validate that the input is a valid any.
     * This doesn't do any checks and always returns true.
     *
     * @return  boolean Whether the input is valid.
     */
    protected function formatDefault()
    {
        return true;
    }
}
