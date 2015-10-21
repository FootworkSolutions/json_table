<?php
namespace JsonTable\Validate\Format;

/**
 * Lexical datetime validator.
 *
 * @package JSON table
 */
class DatetimeValidator extends \JsonTable\Validate\AbstractFormatValidator
{
	/**
	 * Validate that the input is a valid ISO8601 formatted date.
	 *
	 * @access protected
	 *
	 * @return boolean Whether the input is valid.
	 */
	protected function _format_default()
	{
		// Check if this is an ISO8601 datetime format (in the UTC timezone).
		if (true == preg_match('/^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2}):(\d{2})Z$/', $this->_m_input)) {
			// Check that PHP can build a date object from the input.
			return (false !== \DateTime::createFromFormat(DATE_ISO8601, $this->_m_input));
		}
		elseif ($this->_format_date('Y-m-d H:i:s')) {
			return true;
		}
		elseif ($this->_format_date('Y-m-d')) {
			return true;
		}
		elseif ($this->_format_date('H:i:s')) {
			return true;
		}

		// The input didn't match any of the expected formats.
		return false;
	}


	/**
	 * Validate that the input is a valid date in the specified format.
	 *
	 * @access protected
	 *
	 * @param string   The date format to validate against.
	 *
	 * @return boolean Whether the input is valid.
	 */
	protected function _format_date($ps_format)
	{
		$lo_date = \DateTime::createFromFormat($ps_format, $this->_m_input);

		return $lo_date && ($lo_date->format($ps_format) === $this->_m_input);
	}
}
