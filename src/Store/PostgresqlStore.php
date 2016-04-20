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
     * @var string  The name of the table to store the data into.
     */
    private $tableName;

    /**
     * @var string  The name of the primary key column in the table the data is being stored in.
     */
    private $primaryKey;

    /**
     * @var array      Data type and format metadata for each column being inserted.
     *                 The Key is the CSV column position in the file and value is an array of:
     *                     "pdo_type" - The PDO data type
     *                     "type" - The schema data type
     *                     "format" - The schema format.
     */
    private $column_metadata = [];

    /**
     * @var string  The CSV list of columns to be inserted into.
     */
    private $columnList;

    /**
     * @var string  The parameters to be used in the insert statement.
     */
    private $insertParameters;

    /**
     * @var array The current CSV row being stored.
     */
    private $currentCsvRow;

    /**
     * @var int The position of the current CSV row row in the CSV file.
     */
    private $rowNumber;

    /**
     * @var int The position of the current CSV field in the current row.
     */
    private $fieldNumber;


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
     * @param   string  $tableName  The name of the table to save the data in. With optional schema prefix.
     * @param   string  $primaryKey The name of the primary key on the table. [optional] The default is "id".
     *                                  The primary key does not need to be listed in the CSV if it has
     *                                  a serial associated with it.
     *
     * @return  boolean true
     */
    public function store($tableName, $primaryKey = 'id')
    {
        $this->tableName = (string) $tableName;
        $this->primaryKey = (string) $primaryKey;
        Base::openFile();
        $this->setColumns();
        $this->setColumnsMetadata();
        $this->setInsertParameters();
        Base::rewindFilePointerToFirstData();
        $this->rowNumber = 1;
        $this->storeCsvRows();

        return true;
    }


    /**
     * Get the PDO type, schema type & schema format for each column in the CSV file.
     *
     * @return boolean true on success
     */
    private function setColumnsMetadata()
    {
        foreach (Base::$headerColumns as $li_csv_field_position => $ls_csv_column_name) {
            $la_metadata = [];
            $li_csv_field_position += 1;

            $li_schema_key = $this->getSchemaKeyFromName($ls_csv_column_name);
            $lo_schema_field = self::$schemaJson->fields[$li_schema_key];

            $la_metadata['type'] = $this->getColumnType($lo_schema_field);
            $la_metadata['pdo_type'] = self::$pdo_type_mappings[$la_metadata['type']];
            $la_metadata['format'] = $this->getColumnFormat($lo_schema_field);

            $this->column_metadata[$li_csv_field_position] = $la_metadata;
        }

        return true;
    }


    /**
     * Set the columns that will be inserted into.
     * The columns include the "csv_row" field to store the CSV row
     * number to help make error messages more useful.
     *
     * @return  void
     */
    private function setColumns()
    {
        $this->columnList = implode(', ', Base::$headerColumns);
        $this->columnList .= ', csv_row';
    }


    /**
     * Set the insert parameters string.
     *
     * @return  void
     */
    private function setInsertParameters()
    {
        $this->insertParameters = implode(', ', array_fill(0, count(Base::$headerColumns), '?'));
        $this->insertParameters .= ', ?';
    }


    /**
     * Store each of the CSV rows.
     *
     * @return  void
     *
     * @throws  \Exception if the row couldn't be inserted into the database.
     */
    private function storeCsvRows()
    {
        while ($csvRow = Base::loopThroughFileRows()) {
            $this->currentCsvRow = $csvRow;
            
            $insertSql = "INSERT INTO $this->tableName (
                                  $this->columnList
                              )
                              VALUES (
                                  $this->insertParameters
                              )
                              RETURNING
                                $this->primaryKey AS key";

            $statement = self::$pdoConnection->prepare($insertSql);
            $this->fieldNumber = 1;

            foreach ($this->currentCsvRow as &$fieldValue) {
                $columnMetadata = $this->column_metadata[$this->fieldNumber];
                $fieldValue = $this->updateFieldValue($columnMetadata, $fieldValue);
                $statement->bindParam($this->fieldNumber++, $fieldValue, $columnMetadata['pdo_type']);
            }

            $statement->bindParam($this->fieldNumber, $this->rowNumber, \PDO::PARAM_INT);
            $la_result = $statement->execute();

            if (false === $la_result) {
                throw new \Exception("Could not insert row $this->rowNumber into the database.");
            }

            $this->insertedIds[] = $statement->fetch(\PDO::FETCH_ASSOC);
            $this->rowNumber++;
        }
    }


    /**
     * Do any data manipulation required on the specified column's value.
     *
     * @param array     $columnMetadata The metadata about the column.
     * @param string    $fieldValue     The value to update.
     *
     * @return  string  The updated field value.
     */
    private function updateFieldValue($columnMetadata, $fieldValue)
    {
        if ('date' === $columnMetadata['type'] && 'default' !== $columnMetadata['format']) {
            $fieldValue = self::isoDateFromFormat($columnMetadata['format'], $fieldValue);
        }

        if ('boolean' === $columnMetadata['type']) {
            $fieldValue = self::booleanFromFilterBooleans($fieldValue);
        }

        if ('' === $fieldValue || '\N' === $fieldValue) {
            $fieldValue = null;
        }

        return $fieldValue;
    }


    /**
     * Get the returned primary key of any rows that have been stored.
     *
     * @return  array   The primary keys.
     */
    public function getInsertedIds()
    {
        return $this->insertedIds;
    }
}
