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
     * Validate that any specified foreign key constraints have been met.
     *
     * @access  public
     *
     * @return  boolean Does the data meet the foreign key constraints.
     *
     * @throws  \Exception if a foreign key other than postgresql is specified.
     */
    public function validate()
    {
        // Check that a primary key has been specified.
        if (false === property_exists(self::$schemaJson, 'foreignKeys')) {
            // There is no foreign key specified so validate as successfully passed.
            return true;
        }

        // Rewind the CSV file pointer to the first line of data.
        self::rewindFilePointerToFirstData();

        // Loop through the foreign keys.
        foreach (self::$schemaJson->foreignKeys as $foreignKey) {
            // Get the datapackage for this foreign key.
            $dataPackage = $this->getForeignKeyPackage($foreignKey);

            // Only "postgresql" datapackages are currently supported.
            if ('postgresql' !== $dataPackage) {
                throw new \Exception("Only postgresql foreign keys are currently supported.
                Please ensure that the datapackage attribute on all foreign keys is defined
                as &quot;database&quot; or is omitted.");
            }

            // Instantiate the foreign key validator for this datapackage type.
            $validator = $this->instantiateValidator(Analyse::VALIDATION_TYPE_FOREIGN_KEY, $dataPackage);

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

                // Get the position of this field in the CSV file.
                if (false === $csvPosition) {
                    // The field isn't in the CSV.
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

                    $this->setError(Analyse::ERROR_INVALID_FOREIGN_KEY, $errorMessage);
                    $this->statistics['rows_with_errors'][] = $row;

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
     * @access  private
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
}