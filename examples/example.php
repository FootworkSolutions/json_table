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
use \JsonTable\Analyse\Analyse;
use \JsonTable\Store;

// Use composer's autoloading.
require __DIR__ . '/../vendor/autoload.php';
PhpConsole\Helper::register();

/*
 * These are the three things that need to be configured for this
 * example to work.
 */
// Where the file to validate is located.
$ls_file_path = 'example.csv';

// Where the JSON schema is located.
$ls_json_schema_file_path = 'example.json';

// The database connection information.
$ls_pdo_connection = 'pgsql:host=localhost;port=5432;dbname=test;user=postgres;password=mypass';

// The name of the table to store the data in.
$ls_store_table_name = 'json_table_stored_data_test';

try {
    // The validator expects a string for the JSON schema, this allows
    // you to load this from anywhere you like. e.g. a database table,
    // a file etc. In this example we're loading it from a file.
    $ls_schema_json = file_get_contents($ls_json_schema_file_path);

    // Connect to your database.
    $lo_pdo = new PDO($ls_pdo_connection);

    // Instantiate the analysis class.
    $lo_analyser = new Analyse();

    // Let the analyser know where the JSON table schema is.
    $lo_analyser->setSchema($ls_schema_json);

    // Let the analyser know where the CSV file to validate is.
    $lo_analyser->setFile($ls_file_path);

    // Let the analyser know how to communicate with your database.
    // This is used to check foreign keys and store data.
    $lo_analyser->setPdoConnection($lo_pdo);

    // Check whether the file is valid against the schema.
    $lb_file_is_valid = $lo_analyser->validate();

    // Get errors and statistics about the analysis.
    $la_validation_errors = $lo_analyser->getErrors();
    $la_statistics = $lo_analyser->getStatistics();

    // Collect together all the information about this validation.
    $la_return_data = [
        'valid' => $lb_file_is_valid,
        'errors' => $la_validation_errors,
        'statistics' => $la_statistics
    ];

    // If the file is valid, save the data in a PostgreSQL database.
    if ($lb_file_is_valid) {
        // Instantiate the store class.
        $lo_store = Store::load('postgresql');

        if (!$lo_store->store($ls_store_table_name)) {
            throw new \Exception('Could not save the file to the PostgreSQL database.');
        }

        // Get the primary key of the records that were inserted.
        $la_inserted_records = $lo_store->insertedRecords();

        // Add the store operation data to the validation information so there is
        // a complete picture of everything that has happened.
        $la_return_data['inserted_records'] = $la_inserted_records;
    }

    // You will probably want to do something else with this information,
    // like return it to a calling function or as JSON from an API request.
    $ls_html_output = print_r($la_return_data, true);
} catch (\Exception $e) {
    // All JSON Table exceptions are considered to be end user friendly.
    // So if you are allowing users to upload their own files you should be
    // safe to let them see these messages.
    $ls_html_output = print_r($e->getMessage(), true);
}
?>
<html>
<head>
</head>
<body>
    <pre><?php echo $ls_html_output; ?></pre>
</body>
</html>
