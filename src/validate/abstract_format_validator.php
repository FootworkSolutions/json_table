<?php
namespace json_table;

/**
 * Format validator abstract.
 *
 * @package	JSON table
 */
abstract class abstract_format_validator {
	/**
	 * @access	protected
	 * @var	string	The type of validation being done. eg "string", "number".
	 */
	protected $_s_type;

	/**
	 * @access	protected
	 * @var	mixed	The input being validated.
	 */
	protected $_m_input;


	/**
	 * Construct the validator.
	 *
	 * @access	public
	 *
	 * @param	string	$ps_type	The type of validation being validated.
	 */
	public function __construct ($ps_type) {
		$this->_s_type = (string) $ps_type;
	}


	/**
	 * Set the input to validate.
	 *
	 * @access public
	 *
	 * @param	mixed	The input to validate.
	 *
	 * @return void
	 */
	public function set_input ($pm_input) {
		$this->_m_input = $pm_input;
	}


	/**
	 * Check that the input matches the specified format.
	 *
	 * @access	public
	 *
	 * @param	string	$ps_format	The format to validate against.
	 *
	 * @return	boolean	Is the data valid.
	 */
	public function validate_format ($ps_format) {
		// Default the return flag.
		$lb_valid = true;

		// Check that there is a value to validate.
		if ('' === $this->_m_input) {
			return true;
		}

		// Define the name of the method to check this format.
		if ('datetime' === $this->_s_type && 'default' !== $ps_format) {
			$ls_format_method_name = "_format_date";
			$ls_format_parameter = $ps_format;
		}
		else {
			$ls_format_method_name = "_format_$ps_format";
			$ls_format_parameter = null;
		}

		// Check the method exists and then call it.
		if (method_exists($this, $ls_format_method_name)) {
			$lb_valid = $this->$ls_format_method_name($ls_format_parameter);
		}
		else {
			throw new \Exception("Could not find a method to validate the $ps_format format.");
		}

		return $lb_valid;
	}
}