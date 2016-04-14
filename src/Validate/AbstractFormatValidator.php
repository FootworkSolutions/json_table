<?php
namespace JsonTable\Validate;

/**
 * Format validator abstract.
 *
 * @package JSON table
 */
abstract class AbstractFormatValidator
{
    /**
     * @var string The type of validation being done. eg "string", "number".
     */
    protected $type;

    /**
     * @var mixed The input being validated.
     */
    protected $input;


    /**
     * Construct the validator.
     *
     * @param string $type The type of validation being validated.
     */
    public function __construct($type)
    {
        $this->type = (string) $type;
    }


    /**
     * Set the input to validate.
     *
     * @param mixed $input The input to validate.
     *
     * @return void
     */
    public function setInput($input)
    {
        $this->input = $input;
    }


    /**
     * Check that the input matches the specified format.
     *
     * @param string $format The format to validate against.
     *
     * @return boolean Is the data valid.
     *
     * @throws \Exception if the method to validate the format couldn't be found.
     */
    public function validateFormat($format)
    {
        if ('' === $this->input) {
            return true;
        }

        $methodDetails = $this->getFormatMethodDetails($format);

        if (!method_exists($this, $methodDetails['name'])) {
            throw new \Exception("Could not find a method to validate the $format format.");
        }

        $lb_valid = $this->$methodDetails['name']($methodDetails['parameter']);

        return $lb_valid;
    }


    /**
     * Get the name of the method and the parameter to pass to it for the specified format.
     *
     * @param   string  $format         The format to validate against.
     *
     * @return  array   $methodDetails  The name and parameter for the format.
     */
    private function getFormatMethodDetails($format)
    {
        $methodDetails = [];
        $methodDetails['name'] = 'format' . ucwords($format);
        $methodDetails['parameter'] = null;

        if ('datetime' === $this->type && 'default' !== $format) {
            $methodDetails['name'] = "formatDate";
            $methodDetails['parameter'] = $format;
        }

        return $methodDetails;
    }
}
