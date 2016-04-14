<?php
namespace JsonTable\Validate\Format;

use \JsonTable\Validate\AbstractFormatValidator;

/**
 * Lexical number validator.
 *
 * @package JSON table
 */
class NumberValidator extends AbstractFormatValidator
{
    /**
     * Validate that the input is a valid number (float).
     *
     * @return  boolean Whether the input is valid.
     */
    protected function formatDefault()
    {
        return (false !== filter_var($this->input, FILTER_VALIDATE_FLOAT));
    }


    /**
     * Validate that the input is a valid currency.
     *
     * @return  boolean Whether the input is valid.
     */
    protected function formatCurrency()
    {
        // Remove any non-digits from the input.
        //TODO: Validate that any non-digits are valid currency characters.
        $input = preg_filter('/^\D./', '', $this->input);

        if (empty($input)) {
            return true;
        }

        // Check that the remainder of the input matches an expected currency format.
        // This regex is provided by Tim Pietzcker here: http://stackoverflow.com/a/4983648.
        return (true == preg_match('/\b\d{1,3}(?:,?\d{3})*(?:\.\d{2})?\b/', $input));
    }
}
