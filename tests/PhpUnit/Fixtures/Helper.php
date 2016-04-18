<?php
namespace tests\PhpUnit\Fixtures;

use JsonTable\Analyse\Analyse;
use JsonTable\Analyse\Statistics;
use JsonTable\Analyse\Error;
use JsonTable\Base;

class Helper
{
    /**
     * Get the data from the example schema.
     *
     * @return  string  The schema as a string.
     */
    public static function getExampleSchemaString()
    {
        return file_get_contents(self::getBaseDirectory() . '/examples/example.json');
    }


    /**
     * Get the location of the example CSV file.
     *
     * @return  string  The file location.
     */
    public static function getExampleCSVLocation()
    {
        return self::getBaseDirectory() . '/examples/example.csv';
    }


    /**
     * Get the project's base directory.
     *
     * @return  string  The base directory name.
     */
    private static function getBaseDirectory()
    {
        return dirname(dirname(dirname(dirname(__FILE__))));
    }


    /**
     * Create a test CSV file with the specified headers and field data.
     * The file that is created is named "test.csv" and is in the current directory.
     *
     * @param array $pa_column_names The headers.
     * @param array $pa_field_values The field values as a multi-dimensional array.
     *
     * @return void
     */
    public static function createTestCSVFile($columnNames, $fieldValues)
    {
        $file = fopen(self::getBaseDirectory() . '/test.csv', 'w');

        fputcsv($file, $columnNames);

        foreach ($fieldValues as $rowValues) {
            fputcsv($file, $rowValues);
        }

        fclose($file);
    }


    /**
     * Get the location of the test CSV file if it's been created.
     *
     * @return  string|boolean  The file path or false if the file doesn't exist.
     */
    public static function getTestCSVFile()
    {
        return (file_exists(self::getBaseDirectory() . '/test.csv')) ? self::getBaseDirectory() . '/test.csv' : false;
    }


    /**
     * Delete any test CSV file that may have been created.
     *
     * @return  void
     */
    public static function deleteTestCSVFile()
    {
        if (file_exists(self::getBaseDirectory() . '/test.csv')) {
            unlink(self::getBaseDirectory() . '/test.csv');
        }
    }


    /**
     * Reset the state of the system.
     */
    public static function resetSystemState()
    {
        Error::reset();
        Statistics::reset();
        Base::reset();
    }
}




