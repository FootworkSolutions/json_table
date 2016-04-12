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
     * @access  protected
     *
     * @var boolean Should the analysis stop when an error is found.
     */
    protected $stopIfInvalid;

    /**
     * @access  protected
     *
     * @var array   Statistics relating to the file analysis.
     */
    protected $statistics = ['rows_with_errors' => []];

    /**
     * @access  protected
     * @static
     *
     * @var array   Error messages.
     */
    protected static $errors = [];


    /**
     * Analyse the specified file against the loaded schema.
     *
     * @access  public
     *
     * @param   boolean $stopIfInvalid Should the analysis stop when the file is found to be invalid.
     *                                          The default is false.
     *
     * @return  boolean true if the file passes the validation and false if not.
     */
    public function validate($stopIfInvalid = false)
    {
        $this->stopIfInvalid = (bool) $stopIfInvalid;

        self::$errors = [];
        $continueAnalysis = true;

        self::openFile();
        self::setCsvHeaderColumns();

        if (!$this->validateMandatoryColumns()) {
            $continueAnalysis = false;
        }

        if ($continueAnalysis && !$this->validateUnspecifiedColumns() && $this->stopIfInvalid) {
            $continueAnalysis = false;
        }

        $analyseLexical = new Lexical();

        if ($continueAnalysis && !$analyseLexical->validate() && $this->stopIfInvalid) {
            $continueAnalysis = false;
        }

        $analysePrimaryKey = new PrimaryKey();
        
        if ($continueAnalysis && !$analysePrimaryKey->validate() && $this->stopIfInvalid) {
            $continueAnalysis = false;
        }

        if ($continueAnalysis) {
            $analyseForeignKey = new ForeignKey();
            $analyseForeignKey->validate();
        }

        return $this->isFileValid();
    }


    /**
     * Get the statistics about the file analysis.
     *
     * @access  public
     *
     * @return  array   The statistics.
     */
    public function getStatistics()
    {
        $this->statistics['rows_with_errors'] = array_unique($this->statistics['rows_with_errors']);
        $this->statistics['percent_rows_with_errors'] = 0;

        if ($this->statistics['rows_analysed'] > 0) {
            $this->statistics['percent_rows_with_errors'] =
                (count($this->statistics['rows_with_errors']) / $this->statistics['rows_analysed']) * 100;
        }

        return $this->statistics;
    }


    /**
     * Validate that all mandatory columns are present.
     *
     * @access private
     *
     * @return boolean Are all mandatory columns present.
     */
    private function validateMandatoryColumns()
    {
        $validMandatoryColumns = true;

        foreach (self::$schemaJson->fields as $field) {
            if ($this->isColumnMandatory($field)) {
                if (!in_array($field->name, self::$headerColumns)) {
                    $this->setError(Analyse::ERROR_REQUIRED_COLUMN_MISSING, $field->name);
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
     * @access private
     *
     * @return boolean Are all the CSV columns specified in the schema.
     */
    private function validateUnspecifiedColumns()
    {
        $validUnspecifiedColumns = true;

        foreach (self::$headerColumns as $csvColumnName) {
            if (false === $this->getSchemaKeyFromName($csvColumnName)) {
                $this->setError(Analyse::ERROR_UNSPECIFIED_COLUMN, $csvColumnName);
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
     * @access  protected
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
     * @access protected
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
     * @access  private
     *
     * @return  boolean Is the file valid.
     */
    private function isFileValid()
    {
        return (0 === count(self::$errors));
    }


    /**
     * Return all errors.
     *
     * @access  public
     *
     * @return  array   The error messages.
     */
    public function getErrors()
    {
        $errorsFormatted = [];

        // Format the error type with the number of errors of that type.
        foreach (self::$errors as $errorType => $errors) {
            $errorTypeFormatted = sprintf($errorType, count($errors));
            $errorsFormatted[$errorTypeFormatted] = $errors;
        }

        return $errorsFormatted;
    }


    /**
     * Add an error message.
     *
     * @access  protected
     *
     * @param   string  $type   The type of error.
     * @param   string  $error  The error message (or field).
     *
     * @return  void
     */
    protected function setError($type, $error)
    {
        if (!array_key_exists($type, self::$errors)) {
            self::$errors[$type] = [];
        }

        array_push(self::$errors[$type], $error);
    }
}
