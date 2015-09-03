<?php
namespace json_table\format;

/**
 * Lexical any validator.
 *
 * @package	CSV File Validator
 */
class any_validator extends \json_table\abstract_format_validator {
	/**
	 * Validate that the input is a valid any.
	 * This doesn't do any checks and always returns true.
	 *
	 * @access	protected
	 *
	 * @return	boolean	Whether the input is valid.
	 */
	protected function _format_default () {
		return true;
	}
}