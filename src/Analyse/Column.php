<?php
namespace JsonTable\Analyse;

/**
 * Perform analysis on the CSV columns.
 *
 * @package JsonTable
 */
class Column extends Analyse implements AnalyseInterface
{
    /**
     * Validate that all fields are of the correct type, format and pattern.
     * This also checks that each CSV row has the expected number of columns.
     *
     * @return  boolean Is all data lexically valid.
     */
    public function validate()
    {
        if (!$this->validateMandatoryColumns()) {
            return false;
        }

        if (!$this->validateUnspecifiedColumns() && $this->stopIfInvalid) {
            return false;
        }

        return true;
    }
    
    
    /**
     * Validate that all mandatory columns are present.
     *
     * @return boolean Are all mandatory columns present.
     */
    private function validateMandatoryColumns()
    {
        $validMandatoryColumns = true;

        foreach (parent::$schemaJson->fields as $field) {
            if ($this->isColumnMandatory($field)) {
                if (!in_array($field->name, parent::$headerColumns)) {
                    $this->error->setError(Analyse::ERROR_REQUIRED_COLUMN_MISSING, $field->name);
                    $validMandatoryColumns = false;

                    if ($this->stopIfInvalid) {
                        return false;
                    }
                }
            }
        }

        return $validMandatoryColumns;
    }


    /**
     * Check that there are no columns in the CSV that are not specified in the schema.
     *
     * @return boolean Are all the CSV columns specified in the schema.
     */
    private function validateUnspecifiedColumns()
    {
        $validUnspecifiedColumns = true;

        foreach (parent::$headerColumns as $csvColumnName) {
            if (false === $this->getSchemaKeyFromName($csvColumnName)) {
                $this->error->setError(Analyse::ERROR_UNSPECIFIED_COLUMN, $csvColumnName);
                $validUnspecifiedColumns = false;

                if ($this->stopIfInvalid) {
                    return false;
                }
            }
        }

        return $validUnspecifiedColumns;
    }
}