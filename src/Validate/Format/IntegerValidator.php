<?php
namespace JsonTable\Validate\Format;

/**
 * Lexical integer validator.
 *
 * @package	JSON table
 */
class IntegerValidator extends \JsonTable\Validate\AbstractFormatValidator {
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
