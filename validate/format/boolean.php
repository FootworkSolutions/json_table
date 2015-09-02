<?php
namespace json_table\format;

/**
 * Lexical boolean validator.
 *
 * @package	CSV File Validator
 */
class boolean_validator extends \json_table\abstract_format_validator {
	/**
	 * Validate that the input is a valid boolean.
	 * This accepts:
	 * 1 and 0
	 * "1" and "0"
	 * "on" or "ON" and "off" or "OFF"
	 * "yes" or "YES" and "no" or "NO"
	 * true and false
	 *
	 * @access	protected
	 *
	 * @return	boolean	Whether the input is valid.
	 */
	protected function _format_default () {
		$lm_value = (is_string($this->_m_input)) ? strtolower($this->_m_input) : $this->_m_input;

		return (null !== filter_var($lm_value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE));
	}
}