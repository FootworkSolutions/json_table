<?php
namespace JsonTable\Store;

use \JsonTable\Base;

/**
 * Postgresql store.
 *
 * @package JSON table
 */
class PostgresqlStore extends AbstractStore
{
    /**
     * @var array      Data type and format metadata for each column being inserted.
     *                 The Key is the CSV column position in the file and value is an array of:
     *                     "pdo_type" - The PDO data type
     *                     "type" - The schema data type
     *                     "format" - The schema format.
     */
    private $column_metadata = [];


    /**
     * @static
     *
     * @var array Mappings of JSON table types to PDO param types.
     */
    private static $pdo_type_mappings = [
        'any' => \PDO::PARAM_STR,
        'array' => \PDO::PARAM_STR,
        'boolean' => \PDO::PARAM_BOOL,
        'date' => \PDO::PARAM_STR,
        'datetime' => \PDO::PARAM_STR,
        'time' => \PDO::PARAM_STR,
        'integer' => \PDO::PARAM_INT,
        'null' => \PDO::PARAM_NULL,
        'number' => \PDO::PARAM_STR,
        'string' => \PDO::PARAM_STR
    ];


    /**
     * Store the data.
     *
     * @param   string  $ps_table_name  The name of the table to save the data in. With optional schema prefix.
     * @param   string  $ps_primary_key The name of the primary key on the table. [optional] The default is "id".
     *                                  The primary key does not need to be listed in the CSV if it has
     *                                  a serial associated with it.
     *
     * @return  boolean true on success false on failure.
     *
     * @throws  \Exception if the row couldn't be inserted into the database.
     */
    public function store($ps_table_name, $ps_primary_key = 'id')
    {
        // Open the CSV file for reading.
        Base::openFile();

        // Get a list of columns being inserted into from the CSV header row.
        $ls_column_list = implode(', ', Base::$headerColumns);

        // Add the csv_row field to the column list.
        //This field stores the CSV row number to help make error messages more useful.
        $ls_column_list .= ', csv_row';

        // Set the metadata for the CSV columns.
        $this->setColumnsMetadata();

        // Define the parameter list for the statement.
        $ls_insert_parameters = implode(', ', array_fill(0, count(Base::$headerColumns), '?'));

        // Add an additional parameter for the csv_row field.
        $ls_insert_parameters .= ', ?';

        // Rewind the CSV file pointer to the first line of data.
        Base::rewindFilePointerToFirstData();

        // Set the row flag.
        $li_row = 1;

        // Read each row in the file.
        while ($la_row = Base::loopThroughFileRows()) {
            $la_csv_row = $la_row;
            
                // Set up the SQL statement for the insert.
            $ls_insert_sql = "INSERT INTO $ps_table_name (
                                  $ls_column_list
                              )
                              VALUES (
                                  $ls_insert_parameters
                              )
                              RETURNING
                                $ps_primary_key AS key";
            $lo_statement = self::$pdoConnection->prepare($ls_insert_sql);

            // Loop through each column in the CSV row.
            $li_field_number = 1;

            foreach ($la_csv_row as &$lm_field_value) {
                // Get this column's metadata for easy access and readability.
                $la_column_metadata = $this->column_metadata[$li_field_number];

                // Do any data manipulation required on this column.
                // If the type is date and there is a format, convert this to an ISO date.
                if ('date' === $la_column_metadata['type'] && 'default' !== $la_column_metadata['format']) {
                    $lm_field_value = self::isoDateFromFormat($la_column_metadata['format'], $lm_field_value);
                }

                // If the type is boolean ensure that the value is boolean
                // as the validation will pass for "1/0", "on/off" and "yes/no".
                if ('boolean' === $la_column_metadata['type']) {
                    $lm_field_value = self::booleanFromFilterBooleans($lm_field_value);
                }

                // Convert empty strings or special "\N" identifiers to null.
                if ('' === $lm_field_value || '\N' === $lm_field_value) {
                    $lm_field_value = null;
                }

                // Bind the field to the insert statement.
                $lo_statement->bindParam($li_field_number++, $lm_field_value, $la_column_metadata['pdo_type']);
            }

            // Bind the extra parameter for the csv_row field.
            $lo_statement->bindParam($li_field_number, $li_row, \PDO::PARAM_INT);

            // Run the statement to insert the row.
            $la_result = $lo_statement->execute();

            if (false === $la_result) {
                // The query failed.
                throw new \Exception("Could not insert row $li_row into the database.");
            }

            // Add this insert's primary key to the list of inserted columns.
            $this->insertedIds[] = $lo_statement->fetch(\PDO::FETCH_ASSOC);

            $li_row++;
        }

        return true;
    }


    /**
     * Get the PDO type, schema type & schema format for each column in the CSV file.
     *
     * @return boolean true on success
     */
    private function setColumnsMetadata()
    {
        // Get the data type for each of the columns being inserted into.
        foreach (Base::$headerColumns as $li_csv_field_position => $ls_csv_column_name) {
            $la_metadata = [];

            // Compensate for 1 based column reference.
            $li_csv_field_position += 1;

            // Get the schema key for this column name.
            $li_schema_key = $this->getSchemaKeyFromName($ls_csv_column_name);

            // Get the field object for this schema key.
            $lo_schema_field = self::$schemaJson->fields[$li_schema_key];

            // Get the schema type for this column.
            $la_metadata['type'] = $this->getColumnType($lo_schema_field);

            // Set the PDO data type for this column.
            $la_metadata['pdo_type'] = self::$pdo_type_mappings[$la_metadata['type']];

            // Set the format for this column.
            $la_metadata['format'] = $this->getColumnFormat($lo_schema_field);

            // Set the metadata for this column.
            $this->column_metadata[$li_csv_field_position] = $la_metadata;
        }

        return true;
    }
}
