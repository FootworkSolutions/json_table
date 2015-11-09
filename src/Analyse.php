<?php
namespace JsonTable;

/**
 * Analyse data to ensure it validates against a JSON table schema.
 *
 * @package    JSON table
 */
class Analyse extends Base
{
    /**
     * @var string The description for missing mandatory columns.
     */
    const ERROR_REQUIRED_COLUMN_MISSING = '<strong>%d</strong> required column(s) missing:';

    /**
     * @var string The description for CSV columns that are not in the schema.
     */
    const ERROR_UNSPECIFIED_COLUMN = '<strong>%d</strong> unexpected column(s):';

    /**
     * @var string The description for rows with missing columns.
     */
    const ERROR_INCORRECT_COLUMN_COUNT = 'There are the wrong number of columns';

    /**
     * @var string The description for rows with missing columns.
     */
    const ERROR_REQUIRED_FIELD_MISSING_DATA = 'There are <strong>%d</strong> required fields with missing data:';

    /**
     * @var string The description for fields with invalid formats.
     */
    const ERROR_INVALID_FORMAT = 'There are <strong>%d</strong> fields that don\'t have the correct format:';

    /**
     * @var string The description for fields with invalid formats.
     */
    const ERROR_INVALID_PATTERN = 'There are <strong>%d</strong> fields that don\'t have the correct pattern:';

    /**
     * @var string The description for fields with duplicated primary keys.
     */
    const ERROR_DUPLICATE_PRIMARY_KEY = 'There are <strong>%d</strong> rows that have duplicated primary keys:';

    /**
     * @var string The description for fields with invalid foreign keys.
     */
    const ERROR_INVALID_FOREIGN_KEY = 'There are <strong>%d</strong> fields that have invalid foreign keys:';

    /**
     * @var string The format validation type.
     */
    const VALIDATION_TYPE_FORMAT = 'Format';

    /**
     * @var string The foreign key validation type.
     */
    const VALIDATION_TYPE_FOREIGN_KEY = 'ForeignKey';

    /**
     * @access private
     *
     * @var boolean Should the analysis stop when an error is found.
     */
    private $_b_stop_if_invalid;

    /**
     * @access private
     *
     * @var array Statistics relating to the file analysis.
     */
    private $_a_statistics = ['rows_with_errors' => []];

    /**
     * @access protected
     * @static
     *
     * @var array Error messages.
     */
    protected static $_a_errors = [];


    /**
     * Constructor.
     *
     * @access public
     */
    public function __construct()
    {
        // Load the abstract and interface validator classes.
        include_once dirname(__FILE__) . '/Validate/AbstractFormatValidator.php';
        include_once dirname(__FILE__) . '/Validate/InterfaceForeignKeyValidator.php';
    }


    /**
     * Analyse the specified file against the loaded schema.
     *
     * @access public
     *
     * @param boolean $pb_stop_if_invalid Should the analysis stop when the file is found to be invalid. The default is false.
     *
     * @return boolean true if the file passes the validation and false if not.
     */
    public function analyse($pb_stop_if_invalid = false)
    {
        // Set whether to stop if invalid.
        $this->_b_stop_if_invalid = (bool) $pb_stop_if_invalid;

        // Ensure that there are no errors present from a previous run.
        self::$_a_errors = [];

        // Open the CSV file for reading.
        self::_openFile();

        $lb_continue_analysis = true;

        // Set the CSV header columns.
        self::_setCsvHeaderColumns();

        // Validate that the mandatory columns are all present.
        // If this fails, no further analysis will be done regarless of whether the stop on invalid flag is set to false.
        if (!$this->_validateMandatoryColumns()) {
            $lb_continue_analysis = false;
        }

        // Validate that all the CSV columns are in the schema.
        if ($lb_continue_analysis && !$this->_validateUnspecifiedColumns() && $this->_b_stop_if_invalid) {
            $lb_continue_analysis = false;
        }

        // Validate the type and format of the data.
        if ($lb_continue_analysis && !$this->_validateLexical() && $this->_b_stop_if_invalid) {
            $lb_continue_analysis = false;
        }

        // Validate that primary key constraints are met.
        if ($lb_continue_analysis && !$this->_validatePrimaryKey() && $this->_b_stop_if_invalid) {
            $lb_continue_analysis = false;
        }

        // Validate that foreign key constraints are met.
        if ($lb_continue_analysis) {
            $this->_validateForeignKeys();
        }

        // Return whether the file is valid.
        return $this->_isFileValid();
    }


    /**
     * Get the statistics about the file analysis.
     *
     * @access public
     *
     * @return array The statistics.
     */
    public function getStatistics()
    {
        // Remove duplicates from the rows with errors.
        // If a row has multiple errors it will have been added multiple times.
        $this->_a_statistics['rows_with_errors'] = array_unique($this->_a_statistics['rows_with_errors']);

        // Calculate the % of rows with errors.
        if ($this->_a_statistics['rows_analysed'] > 0) {
            $this->_a_statistics['percent_rows_with_errors'] = (count($this->_a_statistics['rows_with_errors']) / $this->_a_statistics['rows_analysed']) * 100;
        }
        else {
            $this->_a_statistics['percent_rows_with_errors'] = 0;
        }

        return $this->_a_statistics;
    }


    /**
     * Validate that all mandatory columns are present.
     *
     * @access private
     *
     * @return boolean Are all mandatory columns present.
     */
    private function _validateMandatoryColumns()
    {
        // Default the returned flag.
        $lb_valid_mandatory_columns = true;

        // Loop through the schema and check for columns marked as mandatory.
        foreach (self::$_o_schema_json->fields as $lo_field) {
            if ($this->_isColumnMandatory($lo_field)) {
                // Check if this column is in the CSV file.
                if (!in_array($lo_field->name, self::$_a_header_columns)) {
                    // The column is missing from the file so add an error and update the returned flag.
                    $this->_setError(Analyse::ERROR_REQUIRED_COLUMN_MISSING, $lo_field->name);
                    $lb_valid_mandatory_columns = false;

                    // Return if execution should stop if invalid.
                    if ($this->_b_stop_if_invalid) {
                        return false;
                    }
                }
            }
        }

        return $lb_valid_mandatory_columns;
    }


    /**
     * Check that there are no columns in the CSV that are not specified in the schema.
     *
     * @access private
     *
     * @return boolean Are all the CSV columns specified in the schema.
     */
    private function _validateUnspecifiedColumns()
    {
        // Default the returned flag.
        $lb_valid_unspecified_columns = true;

        // Loop through the CSV header columns and check that each one is in the scheam.
        foreach (self::$_a_header_columns as $ls_csv_column_name) {
            // Check that the column was found in the schema.
            if (false === $this->_getSchemaKeyFromName($ls_csv_column_name)) {
                // The column is missing from the schema so add an error and update the returned flag.
                $this->_setError(Analyse::ERROR_UNSPECIFIED_COLUMN, $ls_csv_column_name);
                $lb_valid_unspecified_columns = false;

                // Return if execution should stop if invalid.
                if ($this->_b_stop_if_invalid) {
                    return false;
                }
            }
        }

        return $lb_valid_unspecified_columns;
    }


    /**
     * Validate that all fields are of the correct type, format and pattern.
     * This also checks that each CSV row has the expected number of columns.
     *
     * @access private
     *
     * @return boolean Is all data lexically valid.
     */
    private function _validateLexical()
    {
        // Default the returned flag.
        $lb_valid_lexical = true;

        // Rewind the CSV file pointer to the first line of data.
        self::_rewindFilePointerToFirstData();

        // Set the row flag.
        $li_row = 1;

        // Read each row in the file.
        while ($la_csv_row = self::_loopThroughFileRows()) {
            // Check that this row has the expected number of columns.
            $li_column_count = count($la_csv_row);
            $li_header_column_count = count(self::$_a_header_columns);

            if ($li_header_column_count !== $li_column_count) {
                $this->_setError(Analyse::ERROR_INCORRECT_COLUMN_COUNT, "Row $li_row has $li_column_count columns but should have $li_header_column_count.");
                $this->_a_statistics['rows_with_errors'][] = $li_row;
            }

            // Loop through each column of the row.
            for ($li_column_number = 0; $li_column_number < $li_column_count; $li_column_number++) {
                // Get the schema column object for this CSV column.
                $lo_schema_column = $this->_getSchemaColumnFromCsvColumnPosition($li_column_number);

                // Check if this data is mandatory.
                if ($this->_isColumnMandatory($lo_schema_column)) {
                    // Check if the field has any data in it.
                    if ('' === $la_csv_row[$li_column_number]) {
                        // This is a mandatory column without any data in it, so set an error.
                        $this->_setError(Analyse::ERROR_REQUIRED_FIELD_MISSING_DATA, "$lo_schema_column->name on row $li_row is missing.");
                        $this->_a_statistics['rows_with_errors'][] = $li_row;
                        $lb_valid_lexical = false;

                        // Return if execution should stop if invalid.
                        if ($this->_b_stop_if_invalid) {
                            return false;
                        }
                    }
                }

                // Check that the data is valid.
                $ls_type = $this->_getColumnType($lo_schema_column);
                $ls_format = $this->_getColumnFormat($lo_schema_column);

                // Instantiate the format validator for this field type.
                $lo_validator = $this->_instantiateValidator(Analyse::VALIDATION_TYPE_FORMAT, $ls_type);

                // Pass the data to validate to the validator.
                $lo_validator->setInput($la_csv_row[$li_column_number]);

                // Check that the data matches the specified format.
                if (!$lo_validator->validateFormat($ls_format)) {
                    $lb_valid_lexical = false;

                    // This data didn't match the specified format.
                    $this->_setError(Analyse::ERROR_INVALID_FORMAT, "The data in column $lo_schema_column->name on row $li_row doesn't match the required format of $ls_format.");
                    $this->_a_statistics['rows_with_errors'][] = $li_row;

                    // Return if execution should stop if invalid.
                    if ($this->_b_stop_if_invalid) {
                        return false;
                    }
                }

                // Check that the data matches the specified pattern.
                $ls_pattern = $this->_getColumnPattern($lo_schema_column);

                if (!$this->_validatePattern($ls_pattern, $la_csv_row[$li_column_number])) {
                    $lb_valid_lexical = false;

                    // This data didn't match the specified pattern.
                    $this->_setError(Analyse::ERROR_INVALID_PATTERN, "The data in column $lo_schema_column->name on row $li_row doesn't match the required pattern of $ls_pattern.");
                    $this->_a_statistics['rows_with_errors'][] = $li_row;

                    // Return if execution should stop if invalid.
                    if ($this->_b_stop_if_invalid) {
                        return false;
                    }
                }
            }

            $li_row++;
        }

        // Add the number of rows analysed to the statistics.
        $this->_a_statistics['rows_analysed'] = ($li_row - 2);

        return $lb_valid_lexical;
    }


    /**
     * Check that the input matches the specified pattern.
     *
     * @access private
     *
     * @param string $ps_pattern    The pattern to validate against.
     * @param mixed $lm_input    The input to validate.
     *
     * @return boolean Is the data valid.
     */
    private function _validatePattern($ps_pattern, $lm_input)
    {
        // Check that a pattern has been specified and there is some data to analyse.
        if (is_null($ps_pattern) || '' === $lm_input) {
            return true;
        }

        return (false !== filter_var($lm_input, FILTER_VALIDATE_REGEXP, array('options' => array('regexp' => $ps_pattern))));
    }


    /**
     * Validate that any specified primary key contraints have been met.
     *
     * @access private
     *
     * @return boolean Does the data meet the primary key constraints.
     */
    private function _validatePrimaryKey()
    {
        // Check that a primary key has been specified.
        if (false === property_exists(self::$_o_schema_json, 'primaryKey')) {
            // There is no primary key specified so validate as successfully passed.
            return true;
        }

        // Get the primary key fields(s).
        $la_primary_key_fields = (array) self::$_o_schema_json->primaryKey;

        // Define the container for the primary keys for every row in the file.
        $la_file_keys = array();

        // Rewind the CSV file pointer to the first line of data.
        self::_rewindFilePointerToFirstData();

        // Set the row flag.
        $li_row = 1;

        // Read each row in the file.
        while ($la_csv_row = self::_loopThroughFileRows()) {
            // Define the container for the primary key parts for this row.
            $la_row_key_parts = array();

            // Loop through the primary key fields.
            foreach ($la_primary_key_fields as $ls_field_name) {
                // Ensure the field name is lowercase as all field names have been lowercased.
                $ls_field_name = strtolower($ls_field_name);

                // Check that the field exists in the schema.
                if (false === $this->_getSchemaKeyFromName($ls_field_name)) {
                    throw new \Exception("The primary key &quot;$ls_field_name&quot; was not in the file. Primary key columns should be set as required.");
                }

                // Get the position of this field in the CSV file.
                $li_csv_position = $this->_getCsvPositionFromName($ls_field_name);

                // Add the data in this primary key field to the container.
                $la_row_key_parts[] = $la_csv_row[$li_csv_position];
            }

            // Implode the primary key parts together to form a single hash.
            $ls_row_hash = implode(', ', $la_row_key_parts);

            // Check that this primary key hash hasn't already been found in the file.
            if ($lm_existing_key = array_search($ls_row_hash, $la_file_keys)) {
                // A duplicate primary key hash has been found.
                $ls_primary_key_columns = implode(', ', $la_primary_key_fields);
                $ls_error_message = "The data in columns &quot;$ls_primary_key_columns&quot; should be unique, but rows $lm_existing_key &amp; $li_row have the same values of &quot;$ls_row_hash&quot;";
                $this->_setError(Analyse::ERROR_DUPLICATE_PRIMARY_KEY, $ls_error_message);
                $this->_a_statistics['rows_with_errors'][] = $li_row;

                // Return if execution should stop if invalid.
                if ($this->_b_stop_if_invalid) {
                    return false;
                }
            }

            // Add this primary key hash to the file list.
            $la_file_keys[$li_row] = $ls_row_hash;

            $li_row++;
        }

        return true;
    }


    /**
     * Validate that any specified foreign key contraints have been met.
     *
     * @access private
     *
     * @return boolean Does the data meet the foreign key constraints.
     */
    private function _validateForeignKeys()
    {
        // Check that a primary key has been specified.
        if (false === property_exists(self::$_o_schema_json, 'foreignKeys')) {
            // There is no foreign key specified so validate as successfully passed.
            return true;
        }

        // Default the valid foreign keys flag.
        $lb_valid_foreign_keys = true;

        // Rewind the CSV file pointer to the first line of data.
        self::_rewindFilePointerToFirstData();

        // Loop through the foreign keys.
        foreach (self::$_o_schema_json->foreignKeys as $lo_foreign_key) {
            // Get the datapackage for this foreign key.
            $ls_datapackage = $this->_getForeignKeyPackage($lo_foreign_key);

            // Only "postgresql" datapackages are currently supported.
            if ('postgresql' !== $ls_datapackage) {
                throw new \Exception("Only postgresql foreign keys are currently supported. Please ensure that the datapackage attribute on all foreign keys is defined as &quot;database&quot; or is omitted.");
            }

            // Instantiate the foreign key validator for this datapackage type.
            $lo_validator = $this->_instantiateValidator(Analyse::VALIDATION_TYPE_FOREIGN_KEY, $ls_datapackage);

            // Get the fields in the CSV and the resource for this foreign key.
            $la_csv_fields = (array) $lo_foreign_key->fields;
            $la_reference_fields = (array) $lo_foreign_key->reference->fields;
            $la_csv_positions = array();

            // Loop through the CSV fields listed in the foreign key and build up a list of CSV positions these relate to.
            foreach ($la_csv_fields as $ls_csv_field_name) {
                // Ensure the field name is lowercase as all field names have been lowercased.
                $ls_csv_field_name = strtolower($ls_csv_field_name);

                // Check that the field exists in the schema.
                if (false === $this->_getSchemaKeyFromName($ls_csv_field_name)) {
                    throw new \Exception("The foreign key field &quot;$ls_csv_field_name&quot; was not defined in the schema.");
                }

                $li_csv_position = $this->_getCsvPositionFromName($ls_csv_field_name);

                // Get the position of this field in the CSV file.
                if (false === $li_csv_position) {
                    // The field isn't in the CSV.
                    if (1 === count($la_csv_fields)) {
                        // This is the only field in the foreign key so skip the validation of this foreign key.
                        continue 2;
                    }
                    else {
                        // This field is part of a multi field foreign key. Throw an error as this key cannot be validated.
                        throw new \Exception("The foreign key field &quot;$ls_csv_field_name&quot; was not in the CSV file but is required as part of a multi field foreign key.");
                    }
                }
                else {
                    // Add the position of this foreign key related CSV field to the container so the data for it can be retrieved.
                    $la_csv_positions[] = $li_csv_position;
                }
            }

            // Set the row flag.
            $li_row = 1;

            // Read each row in the file.
            while ($la_csv_row = self::_loopThroughFileRows()) {
                // Define the container for the foreign key parts for this row.
                $la_row_key_parts = array();

                // Build up the CSV foreign key hash using the CSV field positions calculated above.
                foreach ($la_csv_positions as $li_csv_position) {
                    $la_row_key_parts[] = $la_csv_row[$li_csv_position];
                }

                // Implode the foreign key parts together to form a single hash.
                $ls_csv_value_hash = implode(', ', $la_row_key_parts);

                // Validate the foreign key.
                if (!$lo_validator->validate($ls_csv_value_hash, $lo_foreign_key->reference->resource, $la_reference_fields)) {
                    $lb_valid_foreign_keys = false;

                    // This hash didn't match a foreign key.
                    $ls_csv_fields = implode(', ', $la_csv_fields);
                    $ls_error_message = "The value(s) of &quot;$ls_csv_value_hash&quot; in column(s) $ls_csv_fields on row $li_row doesn't match a foreign key.";
                    $this->_setError(Analyse::ERROR_INVALID_FOREIGN_KEY, $ls_error_message);
                    $this->_a_statistics['rows_with_errors'][] = $li_row;

                    // Return if execution should stop if invalid.
                    if ($this->_b_stop_if_invalid) {
                        return false;
                    }
                }

                $li_row++;
            }

            // Rewind the CSV file pointer to the first line of data.
            self::_rewindFilePointerToFirstData();
        }

        return true;
    }


    /**
     * Check if the spefified column is mandatory.
     *
     * @access private
     *
     * @param object $po_schema_column    The schema column object to examine.
     *
     * @return boolean Whether the column is mandatory.
     */
    private function _isColumnMandatory($po_schema_column)
    {
        return (property_exists($po_schema_column, 'constraints') && property_exists($po_schema_column->constraints, 'required') && (true === $po_schema_column->constraints->required));
    }


    /**
     * Get the pattern of the specified column.
     *
     * @access private
     *
     * @param object $po_schema_column    The schema column object to examine.
     *
     * @return string The pattern or null if no pattern is specified.
     */
    private function _getColumnPattern($po_schema_column)
    {
        return (property_exists($po_schema_column, 'constraints') && property_exists($po_schema_column->constraints, 'pattern')) ? $po_schema_column->constraints->pattern : null;
    }


    /**
     * Get the package of the specified foreign key.
     *
     * @access private
     *
     * @param object $po_foreign_key The foreign key object to examine.
     *
     * @return string The package for the foreign key.
     */
    private function _getForeignKeyPackage($po_foreign_key)
    {
        // Return the datapackage attribute if it's spefied or default it to "postgresql".
        return (property_exists($po_foreign_key->reference, 'datapackage')) ? $po_foreign_key->reference->datapackage : 'postgresql';
    }


    /**
     * Load and instantiate the specified validator.
     *
     * @access private
     *
     * @param string $ps_validation_type The type of validator to load.
     * @param string $ps_type The type being validated.
     *                            For formats this will be the field type.
     *                            For foreign keys this will be the datapackage type
     *
     * @return object The validation object. Throws an exception on error.
     */
    private function _instantiateValidator($ps_validation_type, $ps_type)
    {
        // For format validation, "Date", "datetime" and "time" all follow the same schema definition rules so just use the datetime format for them all.
        if (Analyse::VALIDATION_TYPE_FORMAT === $ps_validation_type && ('date' === $ps_type || 'time' === $ps_type)) {
            $ps_type = 'datetime';
        }

        // Load the validator file.
        $ps_type_class_name = ucwords($ps_type) . 'Validator';
        $ls_validator_file = dirname(__FILE__) . "/Validate/$ps_validation_type/$ps_type_class_name.php";

        if (file_exists($ls_validator_file) && is_readable($ls_validator_file)) {
            include_once $ls_validator_file;
        }
        else {
            throw new \Exception("Could not load the validator file for $ps_validation_type $ps_type.");
        }

        // Check that the class exists.
        $ls_validator_class = "\\JsonTable\\Validate\\$ps_validation_type\\$ps_type_class_name";

        if (!class_exists($ls_validator_class)) {
            throw new \Exception("Could not find the validator class $ls_validator_class");
        }

        return new $ls_validator_class($ps_type);
    }


    /**
     * Check if the file was found to be valid.
     * This checks for any validation errors.
     *
     * @access private
     *
     * @return boolean Is the file valid.
     */
    private function _isFileValid()
    {
        return (0 === count(self::$_a_errors));
    }


    /**
     * Return all errors.
     *
     * @access public
     *
     * @return array The error messages.
     */
    public function getErrors()
    {
        $la_errors_formatted = [];

        // Format the error type with the number of errors of that type.
        foreach (self::$_a_errors as $ls_error_type => $la_errors) {
            $ls_error_type_formatted = sprintf($ls_error_type, count($la_errors));
            $la_errors_formatted[$ls_error_type_formatted] = $la_errors;
        }

        return $la_errors_formatted;
    }


    /**
     * Add an error message.
     *
     * @access protected
     *
     * @param string The type of error.
     * @param string The error message (or field).
     *
     * @return void
     */
    protected function _setError($ps_type, $ps_error)
    {
        if (!array_key_exists($ps_type, self::$_a_errors)) {
            self::$_a_errors[$ps_type] = array();
        }

        array_push(self::$_a_errors[$ps_type], $ps_error);
    }
}
