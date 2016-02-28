<?php
namespace JsonTable\Validate\Format;

use \JsonTable\Validate\AbstractFormatValidator;

/**
 * Lexical string validator.
 *
 * @package JSON table
 */
class StringValidator extends AbstractFormatValidator
{
    /**
     * Validate that the input is a valid string.
     *
     * @access protected
     *
     * @return boolean Whether the input is valid.
     */
    protected function formatDefault()
    {
        return is_string($this->input);
    }


    /**
     * Validate that the input is a valid email address.
     *
     * @access protected
     *
     * @return boolean Whether the input is valid.
     */
    protected function formatEmail()
    {
        return (false !== filter_var($this->input, FILTER_VALIDATE_EMAIL));
    }


    /**
     * Validate that the input is a valid URI.
     * Although the specification for a URI states
     * that it must have a scheme i.e. "http://" @see http://www.faqs.org/rfcs/rfc2396.html
     *
     * This validator allows the input to miss this off so an input
     * of "www.example.com" will be passed as valid.
     *
     * @access protected
     *
     * @return boolean Whether the input is valid.
     */
    protected function formatUri()
    {
        // Parse the URI to check if there is a schema.
        if ($la_uri_parts = parse_url($this->input)) {
            if (!isset($la_uri_parts['scheme'])) {
                $this->input = "http://$this->input";
            }
        }

        return (false !== filter_var($this->input, FILTER_VALIDATE_URL));
    }


    /**
     * Validate that the input is a valid binary string.
     * As PHP treats all stings as binary, this is currently just a check that the input is a string.
     * TODO: Find a better way of validating that the string is a binary.
     *
     * @access protected
     *
     * @return boolean Whether the input is valid.
     */
    protected function formatBinary()
    {
        return is_string($this->input);
    }
}
