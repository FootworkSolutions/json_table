<?php
namespace json_table\format;

/**
 * Lexical array validator.
 *
 * @package	CSV File Validator
 */
class array_validator extends \json_table\abstract_format_validator {
	/**
	 * Validate that the input is a valid null.
	 *
	 * @access	protected
	 *
	 * @return	boolean	Whether the input is valid.
	 */
	protected function _format_default () {
		return false;
	}
}