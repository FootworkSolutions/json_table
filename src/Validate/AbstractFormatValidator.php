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
    protected $_s_type;

    /**
     * @access protected
     * @var mixed The input being validated.
     */
    protected $_m_input;


    /**
     * Construct the validator.
     *
     * @access public
     *
     * @param string $ps_type The type of validation being validated.
     */
    public function __construct($ps_type)
    {
        $this->_s_type = (string) $ps_type;
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
        $this->_m_input = $pm_input;
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
        if ('' === $this->_m_input) {
            return true;
        }

        // Define the name of the method to check this format.
        $ls_format_method_name = '_format' . ucwords($ps_format);
        $ls_format_parameter = null;

        if ('datetime' === $this->_s_type && 'default' !== $ps_format) {
            $ls_format_method_name = "_formatDate";
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
