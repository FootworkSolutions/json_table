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
            $this->dataPackage = $this->getForeignKeyPackage($foreignKey);

            if (!$this->checkValidDataPackageType()) {
                $this->handleInvalidDataPackageType();
            }

            $validator = $this->instantiateValidator(Analyse::VALIDATION_TYPE_FOREIGN_KEY, $this->dataPackage);

            // Get the fields in the CSV and the resource for this foreign key.
            $csvFields = (array) $foreignKey->fields;
            $referenceFields = (array) $foreignKey->reference->fields;
            $csvPositions = [];

            // Loop through the CSV fields listed in the foreign
            // key and build up a list of CSV positions these relate to.
            foreach ($csvFields as $csvFieldName) {
                // Ensure the field name is lowercase as all field names have been lower-cased.
                $csvFieldName = strtolower($csvFieldName);

                // Check that the field exists in the schema.
                if (false === $this->getSchemaKeyFromName($csvFieldName)) {
                    throw new \Exception("The foreign key field &quot;$csvFieldName&quot;
                    was not defined in the schema.");
                }

                $csvPosition = $this->getCsvPositionFromName($csvFieldName);

                if (false === $csvPosition) {
                    if (1 !== count($csvFields)) {
                        // This field is part of a multi field foreign key.
                        // Throw an error as this key cannot be validated.
                        throw new \Exception("The foreign key field &quot;$csvFieldName&quot;
                        was not in the CSV file but is required as part of a multi field foreign key.");
                    }

                    // This is the only field in the foreign key so skip the validation of this foreign key.
                    continue 2;
                }

                // Add the position of this foreign key related CSV field to the container
                // so the data for it can be retrieved.
                $csvPositions[] = $csvPosition;
            }

            // Set the row flag.
            $row = 1;

            // Read each row in the file.
            while ($csvRow = self::loopThroughFileRows()) {
                // Define the container for the foreign key parts for this row.
                $rowKeyParts = [];

                // Build up the CSV foreign key hash using the CSV field positions calculated above.
                foreach ($csvPositions as $csvPosition) {
                    $rowKeyParts[] = $csvRow[$csvPosition];
                }

                $csvValueHash = implode(', ', $rowKeyParts);

                // Validate the foreign key.
                if (!$validator->validate(
                    $csvValueHash,
                    $foreignKey->reference->resource,
                    $referenceFields
                )) {
                    // This hash didn't match a foreign key.
                    $csvFields = implode(', ', $csvFields);
                    $errorMessage = "The value(s) of &quot;$csvValueHash&quot; in column(s) $csvFields
                    on row $row doesn't match a foreign key.";

                    $this->setError(self::ERROR_INVALID_FOREIGN_KEY, $errorMessage);
                    $this->statistics->setErrorRow($row);

                    if ($this->stopIfInvalid) {
                        return false;
                    }
                }

                $row++;
            }

            self::rewindFilePointerToFirstData();
        }

        return true;
    }

    
    /**
     * Get the package of the specified foreign key.
     *
     * @param   object  $foreignKey The foreign key object to examine.
     *
     * @return  string  The package for the foreign key.
     */
    private function getForeignKeyPackage($foreignKey)
    {
        $propertyExists = property_exists($foreignKey->reference, 'datapackage');
        return $propertyExists ? $foreignKey->reference->datapackage : 'postgresql';
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
}