<?php
namespace JsonTable\Validate;

/**
 * Format validator abstract.
 *
 * @package JSON table
 */
abstract class AbstractFormatValidator
{
    /**
     * @access protected
     * @var string The type of validation being done. eg "string", "number".
     */
    protected $type;

    /**
     * @access protected
     * @var mixed The input being validated.
     */
    protected $input;


    /**
     * Construct the validator.
     *
     * @access public
     *
     * @param string $ps_type The type of validation being validated.
     */
    public function __construct($ps_type)
    {
        $this->type = (string) $ps_type;
    }


    /**
     * Set the input to validate.
     *
     * @access public
     *
     * @param mixed The input to validate.
     *
     * @return void
     */
    public function setInput($pm_input)
    {
        $this->input = $pm_input;
    }


    /**
     * Check that the input matches the specified format.
     *
     * @access public
     *
     * @param string $ps_format The format to validate against.
     *
     * @return boolean Is the data valid.
     */
    public function validateFormat($ps_format)
    {
        // Default the return flag.
        $lb_valid = true;

        // Check that there is a value to validate.
        if ('' === $this->input) {
            return true;
        }

        // Define the name of the method to check this format.
        $ls_format_method_name = 'format' . ucwords($ps_format);
        $ls_format_parameter = null;

        if ('datetime' === $this->type && 'default' !== $ps_format) {
            $ls_format_method_name = "formatDate";
            $ls_format_parameter = $ps_format;
        }

        // Check the method exists and then call it.
        if (!method_exists($this, $ls_format_method_name)) {
            throw new \Exception("Could not find a method to validate the $ps_format format.");
        }

        $lb_valid = $this->$ls_format_method_name($ls_format_parameter);

        return $lb_valid;
    }
}
