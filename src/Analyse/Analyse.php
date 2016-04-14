<?php
namespace JsonTable\Analyse;

use \JsonTable\Base;

/**
 * Analyse data to ensure it validates against a JSON table schema.
 *
 * @package    JSON table
 */
class Analyse extends Base implements AnalyseInterface
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
     * @var string The format validation type.
     */
    const VALIDATION_TYPE_FORMAT = 'Format';

    /**
     * @var string The foreign key validation type.
     */
    const VALIDATION_TYPE_FOREIGN_KEY = 'ForeignKey';

    /**
     * @var boolean Should the analysis stop when an error is found.
     */
    protected $stopIfInvalid;

    /**
     * @var Statistics  Statistics information regarding the analysis.
     */
    protected $statistics;

    /**
     * @var Error  Details of errors found during the analysis.
     */
    protected $error;


    /**
     * Set the dependencies if they've been provided.
     *
     * @param   Statistics  $statistics Statistics information regarding the analysis. Optional.
     * @param   Error       $error      Details of errors found during the analysis. Optional.
     */
    public function __construct(Statistics $statistics = null, Error $error = null)
    {
        $this->statistics = (is_null($statistics)) ? new Statistics() : $statistics;
        $this->error = (is_null($error)) ? new Error() : $error;
    }


    /**
     * Analyse the specified file against the loaded schema.
     *
     * @param   boolean $stopIfInvalid Should the analysis stop when the file is found to be invalid.
     *                                          The default is false.
     *
     * @return  boolean true if the file passes the validation and false if not.
     */
    public function validate($stopIfInvalid = false)
    {
        $this->stopIfInvalid = (bool) $stopIfInvalid;

        $continueAnalysis = true;

        self::openFile();
        self::setCsvHeaderColumns();

        if (!$this->validateMandatoryColumns()) {
            $continueAnalysis = false;
        }

        if ($continueAnalysis && !$this->validateUnspecifiedColumns() && $this->stopIfInvalid) {
            $continueAnalysis = false;
        }

        $analyseLexical = new Lexical($this->statistics);

        if ($continueAnalysis && !$analyseLexical->validate() && $this->stopIfInvalid) {
            $continueAnalysis = false;
        }

        $analysePrimaryKey = new PrimaryKey($this->statistics);
        
        if ($continueAnalysis && !$analysePrimaryKey->validate() && $this->stopIfInvalid) {
            $continueAnalysis = false;
        }

        if ($continueAnalysis) {
            $analyseForeignKey = new ForeignKey($this->statistics);
            $analyseForeignKey->validate();
        }

        return $this->isFileValid();
    }


    /**
     * Return all errors.
     *
     * @return  array   The error messages.
     */
    public function getErrors()
    {
        return $this->error->getErrors();
    }


    /**
     * Return the statistics about this analysis.
     *
     * @return  array   The statistics.
     */
    public function getStatistics()
    {
        return $this->statistics->getStatistics();
    }


    /**
     * Validate that all mandatory columns are present.
     *
     * @return boolean Are all mandatory columns present.
     */
    private function validateMandatoryColumns()
    {
        $validMandatoryColumns = true;

        foreach (self::$schemaJson->fields as $field) {
            if ($this->isColumnMandatory($field)) {
                if (!in_array($field->name, self::$headerColumns)) {
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

        foreach (self::$headerColumns as $csvColumnName) {
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


    /**
     * Check if the specified column is mandatory.
     *
     * @param   object  $schemaColumn    The schema column object to examine.
     *
     * @return  boolean Whether the column is mandatory.
     */
    protected function isColumnMandatory($schemaColumn)
    {
        $propertyExists = property_exists($schemaColumn, 'constraints') &&
                              property_exists($schemaColumn->constraints, 'required') &&
                              (true === $schemaColumn->constraints->required);
        return $propertyExists;
    }


    /**
     * Load and instantiate the specified validator.
     *
     * @param string $validationType The type of validator to load.
     * @param string $type The type being validated.
     *                            For formats this will be the field type.
     *                            For foreign keys this will be the datapackage type
     *
     * @return object The validation object. Throws an exception on error.
     *
     * @throws  \Exception if the validator file couldn't be loaded.
     * @throws  \Exception if the validator class definition couldn't be found.
     */
    protected function instantiateValidator($validationType, $type)
    {
        // For format validation, "Date", "datetime" and "time" all follow the same schema definition rules
        // so just use the datetime format for them all.
        if (Analyse::VALIDATION_TYPE_FORMAT === $validationType && ('date' === $type || 'time' === $type)) {
            $type = 'datetime';
        }

        $typeClassName = ucwords($type) . 'Validator';
        $validatorFile = dirname(dirname(__FILE__)) . "/Validate/$validationType/$typeClassName.php";

        if (!file_exists($validatorFile) || !is_readable($validatorFile)) {
            throw new \Exception("Could not load the validator file for $validationType $type.");
        }

        include_once $validatorFile;

        $validatorClass = "\\JsonTable\\Validate\\$validationType\\$typeClassName";

        if (!class_exists($validatorClass)) {
            throw new \Exception("Could not find the validator class $validatorClass");
        }

        return new $validatorClass($type);
    }


    /**
     * Check if the file was found to be valid.
     * This checks for any validation errors.
     *
     * @return  boolean Is the file valid.
     */
    private function isFileValid()
    {
        return (0 === count($this->error->getErrors()));
    }
}
