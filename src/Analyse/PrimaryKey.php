<?php
namespace JsonTable\Analyse;

/**
 * Perform primary key analysis.
 *
 * @package JsonTable
 */
class PrimaryKey extends Analyse implements AnalyseInterface
{
    /**
     * @var string The description for fields with duplicated primary keys.
     */
    const ERROR_DUPLICATE_PRIMARY_KEY = 'There are <strong>%d</strong> rows that have duplicated primary keys:';

    /**
     * @var array   The current CSV row being analysed.
     */
    private $currentCsvRow;

    /**
     * @var int The position of the current CSV row row in the CSV file.
     */
    private $rowNumber;

    /**
     * @var array   The primary keys for every row in the file.
     */
    private $fileKeys;

    /**
     * @var array   The primary key parts for the current row.
     */
    private $rowKeyParts;

    /**
     * @var array   The primary key fields.
     */
    private $primaryKeyFields;

    /**
     * @var string  The name of the primary key field currently being analysed.
     */
    private $primaryKeyFieldName;

    /**
     * @var string  The hash of the data taken from the primary key fields in the current CSV row.
     */
    private $hash;


    /**
     * Validate that any specified primary key constraints have been met.
     *
     * @return  boolean Does the data meet the primary key constraints.
     *
     *
     */
    public function validate()
    {
        if (false === property_exists(parent::$schemaJson, 'primaryKey')) {
            return true;
        }

        $this->setPrimaryKeyFields();
        $this->fileKeys = [];

        self::rewindFilePointerToFirstData();

        $this->rowNumber= 1;

        while ($this->currentCsvRow = self::loopThroughFileRows()) {
            $this->getPrimaryKeyDataForRow();
            $this->createHash();

            if ($existingKey = $this->isHashUnique()) {
                $this->handleDuplicateHash($existingKey);

                if ($this->stopIfInvalid) {
                    return false;
                }
            }

            $this->fileKeys[$this->rowNumber] = $this->hash;
            $this->rowNumber++;
        }

        return true;
    }


    /**
     * Set the primary key fields.
     *
     * @return  void
     */
    private function setPrimaryKeyFields()
    {
        $this->primaryKeyFields = (array) parent::$schemaJson->primaryKey;
    }


    /**
     * Check that there is a column in the JSON table schema file for the current primary key field.
     *
     * @return  void
     *
     * @throws  \Exception if the primary key was not in the schema file.
     */
    private function checkColumnExistsInSchema()
    {
        if (false === $this->getSchemaKeyFromName($this->primaryKeyFieldName)) {
            throw new \Exception("The primary key &quot;$this->primaryKeyFieldName&quot; was not in the file.
                    Primary key columns should be set as required.");
        }
    }


    /**
     * Get the data in the CSV column for the current primary key column.
     *
     * @return  string  The data in the column.
     */
    private function csvDataForPrimaryKeyColumn()
    {
        $csvPosition = $this->getCsvPositionFromName($this->primaryKeyFieldName);
        return $this->currentCsvRow[$csvPosition];
    }


    /**
     * Get the data in the primary key columns for the current CSV row.
     *
     * @return  void
     */
    private function getPrimaryKeyDataForRow()
    {
        $this->rowKeyParts = [];

        foreach ($this->primaryKeyFields as $fieldName) {
            $this->primaryKeyFieldName = strtolower($fieldName);
            $this->checkColumnExistsInSchema();
            $this->rowKeyParts[] = $this->csvDataForPrimaryKeyColumn();
        }
    }


    /**
     * Create a hash of the data taken from the primary key fields in the current CSV row.
     *
     * @return  void
     */
    private function createHash()
    {
        $this->hash = implode(', ', $this->rowKeyParts);
    }


    /**
     * Check whether the current hash has already been created for this file.
     *
     * @return  boolean|int False if this row's primary key hash is unique
     *                      or the number of the row with the same hash if it's not.
     */
    private function isHashUnique()
    {
        return array_search($this->hash, $this->fileKeys);
    }


    /**
     * Handle the current hash not being unique.
     *
     * @param   int $existingKey    The number of the row with the same hash.
     *
     * @return  void
     */
    private function handleDuplicateHash($existingKey)
    {
        $primaryKeyColumns = implode(', ', $this->primaryKeyFields);
        $errorMessage = "The data in columns &quot;$primaryKeyColumns&quot; should be unique,
                but rows $existingKey &amp; $this->rowNumber have the same values of &quot;$this->hash&quot;";

        $this->error->setError(self::ERROR_DUPLICATE_PRIMARY_KEY, $errorMessage);
        $this->statistics->setErrorRow($this->rowNumber);
    }
}