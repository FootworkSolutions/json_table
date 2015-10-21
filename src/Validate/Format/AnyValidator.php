<?php
namespace JsonTable\Validate\Format;

/**
 * Lexical any validator.
 *
 * @package JSON table
 */
class AnyValidator extends \JsonTable\Validate\AbstractFormatValidator
{
	/**
	 * Validate that the input is a valid any.
	 * This doesn't do any checks and always returns true.
	 *
	 * @access protected
	 *
	 * @return boolean Whether the input is valid.
	 */
	protected function _format_default()
	{
		return true;
	}
}
