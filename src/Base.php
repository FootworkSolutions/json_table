<?php
namespace JsonTable;

/**
 * @package    JSON table
 */
abstract class Base
{
    /**
     * @static
     *
     * @var string Schema JSON
     */
    protected static $schemaJson;

    /**
     * @static
     *
     * @var string The path and name of the file to analyse.
     */
    protected static $fileName;

    /**
     * @static
     *
     * @var array The columns found in the header.
     *                This is used to validate that each row has the correct number of columns
     *                and to get the column name from it's position.
     */
    protected static $headerColumns;

    /**
     * @static
     *
     * @var object The SplFileObject of the CSV file.
     */
    protected static $file;

    /**
     * @var object The PDO object.
     */
    public static $pdoConnection;


    /**
     * Set the schema.
     *
     * @param string $schemaJson The schema conforming to the JSON table schema specification.
     * @see http://dataprotocols.org/json-table-schema
     *
     * @return void
     *
     * @throws \Exception if the schema is not a valid JSON string.
     * @throws \Exception if the schema is an invalid data type.
     */
    public function setSchema($schemaJson)
    {
        if (is_string($schemaJson)) {
            if (is_null($schemaJson = json_decode($schemaJson))) {
                throw new \Exception('The schema is not a valid JSON string.');
            }
        } elseif (!is_object($schemaJson)) {
            throw new \Exception('Invalid schema data type.');
        }

        foreach ($schemaJson->fields as &$field) {
            $field->name = strtolower($field->name);
        }
        unset($field);

        self::$schemaJson = $schemaJson;
    }


    /**
     * Set the file.
     * This checks that the file exists.
     *
     * @param   string  $fileName    The path and name of the file to analyse.
     * @see http://dataprotocols.org/json-table-schema
     *
     * @return  boolean Whether the file was successfully set.
     */
    public function setFile($fileName)
    {
        if (file_exists($fileName)) {
            self::$fileName = (string) $fileName;
            return true;
        }

        return false;
    }


    /**
     * Set the database connection.
     *
     * @static
     *
     * @param object $pdoConnection The PDO object.
     *
     * @return boolean Whether the connection was valid.
     */
    public function setPdoConnection($pdoConnection)
    {
        if ($pdoConnection instanceof \PDO) {
            self::$pdoConnection = $pdoConnection;
            return true;
        }

        return false;
    }


    /**
     * Open a handle to the file to be analysed.
     *
     * @static
     *
     * @return void
     *
     * @throws \Exception if a CSV file has not been set.
     */
    public static function openFile()
    {
        if (empty(self::$fileName)) {
            throw new \Exception('CSV file not set.');
        }

        self::$file = new \SplFileObject(self::$fileName);
        self::$file->setFlags(\SplFileObject::READ_CSV | \SplFileObject::SKIP_EMPTY);
    }


    /**
     * Set the CSV header columns from those in the file.
     * These are stored in lowercase as all column to schema checking is considered as case insensitive.
     *
     * @static
     *
     * @return true on success.
     */
    public static function setCsvHeaderColumns()
    {
        self::$file->rewind();
        self::$headerColumns = array_map('strtolower', self::$file->current());
        return true;
    }


    /**
     * Rewind the CSV file pointer to the first line of data.
     *
     * @static
     *
     * @return void
     */
    protected static function rewindFilePointerToFirstData()
    {
        self::$file->seek(1);
    }


    /**
     * Get the data from the current CSV file row and move the pointer on to the next row.
     *
     * @static
     *
     * @return array|boolean The CSV data or false if the end of the file has been reached.
     */
    protected static function loopThroughFileRows()
    {
        if (self::$file->eof()) {
            return false;
        }

        $csvRow = self::$file->current();
        self::$file->next();

        return $csvRow;
    }


    /**
     * Get the key of the field with the specified name from the schema.
     * This can be used to validate that a column exists in the schema.
     *
     * @param string $fieldName The field name.
     *
     * @return int The key ID or false if the field is not found.
     */
    protected function getSchemaKeyFromName($fieldName)
    {
        foreach (self::$schemaJson->fields as $key => $field) {
            if ($field->name === $fieldName) {
                return $key;
            }
        }

        return false;
    }


    /**
     * Get the position of the field with the specified name from the CSV file.
     * This can be used to validate that a column exists in the CSV file.
     *
     * @param string $fieldName The field name.
     *
     * @return int The position or false if the field is not found.
     */
    protected function getCsvPositionFromName($fieldName)
    {
        return array_search($fieldName, self::$headerColumns);
    }


    /**
     * Get the schema object for a column, given the columns position in the CSV file.
     *
     * @param int $csvColumnPosition The position of the column in the CSV file.
     *
     * @return object The schema column.
     */
    protected function getSchemaColumnFromCsvColumnPosition($csvColumnPosition)
    {
        $csvColumnName = self::$headerColumns[$csvColumnPosition];
        $schemaKey = $this->getSchemaKeyFromName($csvColumnName);

        return self::$schemaJson->fields[$schemaKey];
    }


    /**
     * Get the type of the specified column.
     *
     * @param object $schemaColumn The schema column object to examine.
     *
     * @return string The type.
     */
    protected function getColumnType($schemaColumn)
    {
        return (property_exists($schemaColumn, 'type')) ? $schemaColumn->type : 'string';
    }


    /**
     * Get the format of the specified column.
     *
     * @param object $schemaColumn The schema column object to examine.
     *
     * @return string The format or null if no format is specified.
     */
    protected function getColumnFormat($schemaColumn)
    {
        return (property_exists($schemaColumn, 'format')) ? $schemaColumn->format : 'default';
    }
}
