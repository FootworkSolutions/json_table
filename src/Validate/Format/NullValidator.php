<?php
namespace JsonTable\Validate\Format;

/**
 * Lexical boolean validator.
 *
 * @package JSON table
 */
class NullValidator extends \JsonTable\Validate\AbstractFormatValidator
{
	/**
	 * Validate that the input is a valid null.
	 * This actually has to check that the value is an empty string as all inputs are received as strings.
	 *
	 * @access protected
	 *
	 * @return boolean Whether the input is valid.
	 */
	protected function _format_default()
	{
		return ('' === $this->_m_input || '\N' === $this->_m_input);
	}
}
