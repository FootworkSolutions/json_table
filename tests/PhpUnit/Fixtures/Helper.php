<?php
namespace tests\PhpUnit\Fixtures;

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
}




