<?php
namespace JsonTable\Validate\Format;

/**
 * Lexical boolean validator.
 *
 * @package JSON table
 */
class BooleanValidator extends \JsonTable\Validate\AbstractFormatValidator
{
	/**
	 * Validate that the input is a valid boolean.
	 * This accepts:
	 * 1 and 0
	 * "1" and "0"
	 * "on" or "ON" and "off" or "OFF"
	 * "yes" or "YES" and "no" or "NO"
	 * true and false
	 *
	 * @access protected
	 *
	 * @return boolean Whether the input is valid.
	 */
	protected function _format_default()
	{
		$lm_value = (is_string($this->_m_input)) ? strtolower($this->_m_input) : $this->_m_input;

		return (null !== filter_var($lm_value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE));
	}
}
