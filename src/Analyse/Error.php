<?php
namespace JsonTable\Analyse;

/**
 * Details of errors found during an analysis.
 *
 * @package    JSON table
 */
class Error
{
    /**
     * @static
     *
     * @var array   Error messages.
     */
    private static $errors = [];


    /**
     * Get all errors that have been set during the analysis.
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
     * @param   string  $type   The type of error.
     * @param   string  $error  The error message (or field).
     *
     * @return  void
     */
    public function setError($type, $error)
    {
        if (!array_key_exists($type, self::$errors)) {
            self::$errors[$type] = [];
        }

        array_push(self::$errors[$type], $error);
    }
}