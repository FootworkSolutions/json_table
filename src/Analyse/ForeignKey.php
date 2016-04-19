<?php
namespace JsonTable\Analyse;

/**
 * Perform primary key analysis.
 *
 * @package JsonTable
 */
class ForeignKey extends Analyse implements AnalyseInterface
{
    /**
     * @var string The description for fields with invalid foreign keys.
     */
    const ERROR_INVALID_FOREIGN_KEY = 'There are <strong>%d</strong> fields that have invalid foreign keys:';

    /**
     * @var string  The name of the datapackage the current foreign key references.
     */
    private $dataPackage;

    /**
     * @var object  The validator.
     */
    private $validator;

    /**
     * @var object  The current foreign key being analysed.
     */
    private $foreignKey;

    /**
     * @var array   The position of the foreign key columns in the CSV file.
     */
    private $csvPositions = [];

    /**
     * @var string  The current foreign key field having it's CSV file position found.
     */
    private $csvFieldName;

    /**
     * @var int The current CSV row being analysed.
     */
    private $currentCsvRow;



    /**
     * Validate that any specified foreign key constraints have been met.
     *
     * @return  boolean Does the data meet the foreign key constraints.
     *
     * @throws  \Exception if a foreign key other than postgresql is specified.
     */
    public function validate()
    {
        if (false === property_exists(self::$schemaJson, 'foreignKeys')) {
            return true;
        }

        self::rewindFilePointerToFirstData();

        foreach (self::$schemaJson->foreignKeys as $foreignKey) {
            $this->foreignKey = $foreignKey;
            $this->dataPackage = $this->getForeignKeyPackage();

            if (!$this->checkValidDataPackageType()) {
                $this->handleInvalidDataPackageType();
            }

            $this->setValidator();
            $this->setFieldArrays();

            if (!$this->getForeignKeyCsvPositions()) {
                return true;
            }

            $this->currentCsvRow = 1;

            if (!$this->validateCsvRows()) {
                return false;
            }

            self::rewindFilePointerToFirstData();
        }

        return true;
    }

    
    /**
     * Get the package of the specified foreign key.
     *
     * @return  string  The package for the foreign key.
     */
    private function getForeignKeyPackage()
    {
        $propertyExists = property_exists($this->foreignKey->reference, 'datapackage');
        return $propertyExists ? $this->foreignKey->reference->datapackage : 'postgresql';
    }


    /**
     * Check that the data package for the current foreign key is a valid package type.
     *
     * @return  boolean Whether the data package is valid
     */
    private function checkValidDataPackageType()
    {
        return ('postgresql' === $this->dataPackage);
    }


    /**
     * Handle an invalid data package being referenced in a foreign key.
     *
     * @return  void
     *
     * @throws  \Exception if the data package is not valid.
     */
    private function handleInvalidDataPackageType()
    {
        throw new \Exception("Only postgresql foreign keys are currently supported.
                Please ensure that the datapackage attribute on all foreign keys is defined
                as &quot;database&quot; or is omitted.");
    }


    /**
     * Set the validator property.
     * The type of validator is taken from the type of datapackage.
     *
     * @return  void
     */
    private function setValidator()
    {
        $this->validator = $this->instantiateValidator(Analyse::VALIDATION_TYPE_FOREIGN_KEY, $this->dataPackage);
    }


    /**
     * Ensure that the "field" and "reference field" properties of the foreign key are both arrays.
     *
     * @return  void
     */
    private function setFieldArrays()
    {
        $this->foreignKey->fields = (array) $this->foreignKey->fields;
        $this->foreignKey->reference->fields = (array) $this->foreignKey->reference->fields;
    }


    /**
     * Loop through the CSV fields listed in the foreign
     * key and build up a list of CSV positions these relate to.
     *
     * @return boolean  Whether any CSV field positions have been found.
     *
     * @throws  \Exception if a foreign key field is not defined in the schema file.
     * @throws  \Exception if a multi-field foreign key field is not in the CSV file.
     */
    private function getForeignKeyCsvPositions()
    {
        foreach ($this->foreignKey->fields as $this->csvFieldName) {
            $this->csvFieldName = strtolower($this->csvFieldName);

            if (false === $this->getSchemaKeyFromName($this->csvFieldName)) {
                throw new \Exception("The foreign key field &quot;$this->csvFieldName&quot;
                    was not defined in the schema.");
            }

            $csvPosition = $this->getCsvPositionFromName($this->csvFieldName);

            if (false === $csvPosition) {
                if (1 !== count($this->foreignKey->fields)) {
                    throw new \Exception("The foreign key field &quot;$this->csvFieldName&quot;
                        was not in the CSV file but is required as part of a multi field foreign key.");
                }

                return false;
            }

            $this->csvPositions[] = $csvPosition;

            return true;
        }
    }


    /**
     * Analyse and check if each CSV row has a valid foreign key.
     *
     * @return  bool    Whether all the CSV rows are valid.
     */
    private function validateCsvRows()
    {
        while ($csvRow = self::loopThroughFileRows()) {
            $rowKeyParts = [];

            foreach ($this->csvPositions as $csvPosition) {
                $rowKeyParts[] = $csvRow[$csvPosition];
            }

            $csvValueHash = implode(', ', $rowKeyParts);

            if (!$this->validator->validate(
                $csvValueHash,
                $this->foreignKey->reference->resource,
                $this->foreignKey->reference->fields
            )) {
                $this->handleInvalidForeignKey($csvValueHash);

                if ($this->stopIfInvalid) {
                    return false;
                }
            }

            $this->currentCsvRow++;
        }
    }


    /**
     * Handle a CSV row having an invalid foreign key.
     *
     * @param   string  $csvValueHash   The foreign key hash that couldn't be matched.
     *
     * @return  void
     */
    private function handleInvalidForeignKey($csvValueHash)
    {
        $csvFieldCsv = implode(', ', $this->foreignKey->fields);
        $errorMessage = "The value(s) of &quot;$csvValueHash&quot; in column(s) $csvFieldCsv
                    on row $this->currentCsvRow doesn't match a foreign key.";

        $this->error->setError(self::ERROR_INVALID_FOREIGN_KEY, $errorMessage);
        $this->statistics->setErrorRow($this->currentCsvRow);
    }
}