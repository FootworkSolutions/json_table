<?php
/**
 * This is an example of how you can use the JSON table module to validate
 * data from a CSV file against your own schema definition and then store
 * it in a PostgreSQL database.
 *
 * It's expected that this code should be used as a guide. Most likely
 * this code would converted into a function in an application or in an
 * API endpoint.
 *
 * To help make intended use of variables more understandable, this file,
 * along with the source code uses Hungarian Notation in the variable names.
 *
 * @see https://en.wikipedia.org/wiki/Hungarian_notation
 */

// Define where the file and schema are located.
// You will probably want to add some handling of what to do if they're not there.
$ls_full_file_path = 'example.csv';
$ls_schema_json = 'example.json';

try {
	// Instantiate the class that will do the analysis.
	$lo_analyser = new \JsonTable\Analyse();

	// Let the analyser know where the schema and file are.
	$lo_analyser->set_schema($ls_schema_json);
	$lo_analyser->set_file($ls_file_path);

	// Check whether the file is valid against the schema.
	$lb_file_is_valid = $lo_analyser->analyse();

	// Get errors and statistics about the analysis.
	$la_validation_errors = $lo_analyser->get_errors();
	$la_statistics = $lo_analyser->get_statistics();

	// If the file is valid, save the data in a PostgreSQL database.
	if ($lb_file_is_valid) {
		// Load and instantiate the store class.
		$lo_store = \JsonTable\Store::load('postgresql');

		// Save the data.
		$ls_store_table_name = 'your_table_name';
		$ls_store_table_name = "import.t_json_table_test";

		if (!$lo_store->store($ls_store_table_name)) {
			throw new \Exception ('Could not save the file to the PostgreSQL database.');
		}

		// Get the primary key of the records that were inserted.
		$la_inserted_records = $lo_store->inserted_records();

		// Collect together all the information about this validation and store operation.
		$la_return_data = [
			'valid' => $lb_file_is_valid,
			'errors' => $la_validation_errors,
			'statistics' => $la_statistics,
			'inserted_records' => $la_inserted_records
		];

		// You will probably want to do something else with this information,
		// like return it to a calling function or as JSON from an API request.
		var_dump($la_return_data);
	}
} catch (\Exception $e) {
	// All JSON Table exceptions are considered to be end user friendly.
	// So if you are allowing users to upload their own files you should be
	// safe to let them see these messages.
	var_dump($e->getMessage());
}
