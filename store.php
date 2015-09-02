<?php
namespace json_table;

// Load the base class.
require_once dirname(__FILE__) . '/base.php';

/**
 * Store the data using the JSON table schema to determine the data structure.
 *
 * @package	JSON table
 */
class store extends base {
	/**
	 * Load and instantiate the specified store.
	 *
	 * @access	private
	 *
	 * @param	string	$ps_store_type	The type of store to load.
	 *
	 * @return	object	The store object. Throws an exception on error.
	 */
	public static function load ($ps_store_type) {
		// Load the abstract store file.
		$ls_abstract_store_file = dirname(__FILE__) . "/store/abstract_store.php";

		if (file_exists($ls_abstract_store_file) && is_readable($ls_abstract_store_file)) {
			include_once $ls_abstract_store_file;
		}
		else {
			throw new \Exception("Could not load the abstract store file.");
		}

		// Load the store file for the specified type.
		$ls_store_file = dirname(__FILE__) . "/store/$ps_store_type.php";

		if (file_exists($ls_store_file) && is_readable($ls_store_file)) {
			include_once $ls_store_file;
		}
		else {
			throw new \Exception("Could not load the store file for $ps_store_type.");
		}

		// Check that the class exists.
		$ls_store_class = "\\json_table\\store\\$ps_store_type" . "_store";

		if (!class_exists($ls_store_class)) {
			throw new \Exception("Could not find the store class $ls_store_class");
		}

		return new $ls_store_class();
	}
}