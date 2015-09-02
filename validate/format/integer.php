<?php
namespace json_table\format;

/**
 * Lexical integer validator.
 *
 * @package	CSV File Validator
 */
class integer_validator extends \json_table\abstract_format_validator {
	/**
	 * Validate that the input is a valid integer.
	 *
	 * @access	protected
	 *
	 * @return	boolean	Whether the input is valid.
	 */
	protected function _format_default () {
		return (false !== filter_var($this->_m_input, FILTER_VALIDATE_INT));
	}
}