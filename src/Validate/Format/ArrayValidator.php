<?php
namespace JsonTable\Validate\Format;

/**
 * Lexical array validator.
 *
 * @package JSON table
 */
class ArrayValidator extends \JsonTable\Validate\AbstractFormatValidator
{
    /**
     * Validate that the input is a valid null.
     *
     * @access protected
     *
     * @return boolean Whether the input is valid.
     */
    protected function _formatDefault()
    {
        return false;
    }
}
