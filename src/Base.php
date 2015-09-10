<?php
namespace JsonTable;

/**
 * @package	JSON table
 */
abstract class Base {
	/**
	 * @access	protected
	 * @static
	 *
	 * @var	string	Schema JSON
	 */
	protected static $_o_schema_json;

	/**
	 * @access	protected
	 * @static
	 *
	 * @var	string	The path and name of the file to analyse.
	 */
	protected static $_s_file_name;

	/**
	 * @access	protected
	 * @static
	 *
	 * @var	array	The columns found in the header.
	 *				This is used to validate that each row has the correct number of columns
	 *				and to get the column name from it's position.
	 */
	protected static $_a_header_columns;

	/**
	 * @access	protected
	 * @static
	 *
	 * @var	object	The SplFileObject of the CSV file.
	 */
	protected static $_o_file;


	/**
	 * Set the schema.
	 *
	 * @access public
	 *
	 * @param	string|object	$ps_schema_json 	The schema conforming to the JSON table schema specification.
	 * @see		http://dataprotocols.org/json-table-schema
	 *
	 * @return	void
	 */
	public function set_schema ($pm_schema_json) {
		// Check if a JSON string or object has been provided.
		if (is_string($pm_schema_json)) {
			// Convert the string to an object and check it is valid.
			if (is_null($pm_schema_json = json_decode($pm_schema_json))) {
				throw new \Exception('The schema is not a valid JSON string.');
			}
		}
		elseif (!is_object($pm_schema_json)) {
			throw new \Exception('Invalid schema data type.');
		}

		// Convert all field names to be lowercase.
		foreach ($pm_schema_json->fields as &$lo_field) {
			$lo_field->name = strtolower($lo_field->name);
		}
		unset($lo_field);

		self::$_o_schema_json = $pm_schema_json;
	}


	/**
	 * Set the file.
	 *
	 * @access public
	 *
	 * @param	string|object	$ps_file 	The path and name of the file to analyse.
	 * @see	http://dataprotocols.org/json-table-schema
	 *
	 * @return	void
	 */
	public function set_file ($ps_file_name) {
		// Set the file to analyse.
		self::$_s_file_name = (string) $ps_file_name;
	}


	/**
	 * Open a handle to the file to be analysed.
	 *
	 * @access	public
	 * @static
	 *
	 * @return	void
	 */
	protected static function _open_file () {
		// Check that a CSV file has been set.
		if (empty(self::$_s_file_name)) {
			throw new \Exception('CSV file not set.');
		}

		// Construct a new file object.
		self::$_o_file = new \SplFileObject(self::$_s_file_name);

		// Set the flags to read the file as a CSV and to skip any empty rows.
		self::$_o_file->setFlags(\SplFileObject::READ_CSV | \SplFileObject::SKIP_EMPTY);
	}


	/**
	 * Set the CSV header columns from those in the file.
	 * These are stored in lowercase as all column to schema checking is considered as case insensitive.
	 *
	 * @access protected
	 * @static
	 *
	 * @return true on success or throws exception on error.
	 */
	protected static function _set_csv_header_columns () {
		// Rewind to first line.
		self::$_o_file->rewind();

		// Get the first line and convert the header columns to lowercase.
		self::$_a_header_columns = array_map('strtolower', self::$_o_file->current());

		return true;
	}


	/**
	 * Rewind the CSV file pointer to the first line of data.
	 *
	 * @access	protected
	 * @static
	 *
	 * @return	void
	 */
	protected static function _rewind_file_pointer_to_first_data () {
		// Rewind to first line.
		self::$_o_file->seek(1);
	}


	/**
	 * Get the data from the current CSV file row and move the pointer on to the next row.
	 *
	 * @access	public
	 * @static
	 *
	 * @return	array|boolean	The CSV data or false if the end of the file has been reached.
	 */
	protected static function _loop_through_file_rows () {
		// Check if the end of file has been reached.
		if (self::$_o_file->eof()) {
			return false;
		}

		$la_csv_row = self::$_o_file->current();
		self::$_o_file->next();

		return $la_csv_row;
	}


	/**
	 * Get the key of the field with the specified name from the schema.
	 * This can be used to validate that a column exists in the schema.
	 *
	 * @access	protected
	 *
	 * @param	string	$ps_field_name	The field name
	 *
	 * @return	int	The key ID or false if the field is not found.
	 */
	protected function _get_schema_key_from_name ($ps_field_name) {
		foreach (self::$_o_schema_json->fields as $li_key => $lo_field) {
			if ($lo_field->name === $ps_field_name) {
				return $li_key;
			}
		}

		return false;
	}


	/**
	 * Get the position of the field with the specified name from the CSV file.
	 * This can be used to validate that a column exists in the CSV file.
	 *
	 * @access	protected
	 *
	 * @param	string	$ps_field_name	The field name
	 *
	 * @return	int	The position or false if the field is not found.
	 */
	protected function _get_csv_position_from_name ($ps_field_name) {
		return array_search($ps_field_name, self::$_a_header_columns);
	}


	/**
	 * Get the schema object for a column, given the columns posion in the CSV file.
	 *
	 * @access	protected
	 *
	 * @param	int	$pi_csv_column_position	The position of the column in the CSV file.
	 *
	 * @return 	object	The schema column.
	 */
	protected function _get_schema_column_from_csv_column_position ($pi_csv_column_position) {
		// Get the column name for this column position.
		$ls_csv_column_name = self::$_a_header_columns[$pi_csv_column_position];

		// Get the schema key for this column name.
		$li_schema_key = $this->_get_schema_key_from_name($ls_csv_column_name);

		// Return the field object for this schema field key.
		return self::$_o_schema_json->fields[$li_schema_key];
	}


	/**
	 * Get the type of the specified column.
	 *
	 * @access	protected
	 *
	 * @param	object	$po_schema_column	The schema column object to examine.
	 *
	 * @return	string	The type.
	 */
	protected function _get_column_type ($po_schema_column) {
		// If no type is set, the default should be "string".
		return (property_exists($po_schema_column, 'type')) ? $po_schema_column->type : 'string';
	}


	/**
	 * Get the format of the specified column.
	 *
	 * @access	protected
	 *
	 * @param	object	$po_schema_column	The schema column object to examine.
	 *
	 * @return	string	The format or null if no format is specified.
	 */
	protected function _get_column_format ($po_schema_column) {
		return (property_exists($po_schema_column, 'format')) ? $po_schema_column->format : 'default';
	}
}
