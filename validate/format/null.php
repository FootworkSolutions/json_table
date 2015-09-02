<?php
namespace json_table\format;

/**
 * Lexical boolean validator.
 *
 * @package	CSV File Validator
 */
class null_validator extends \json_table\abstract_format_validator {
	/**
	 * Validate that the input is a valid null.
	 * This actually has to check that the value is an empty string as all inputs are received as strings.
	 *
	 * @access	protected
	 *
	 * @return	boolean	Whether the input is valid.
	 */
	protected function _format_default () {
		return ('' === $this->_m_input || '\N' === $this->_m_input);
	}
}