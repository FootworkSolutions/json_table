<?php
namespace tests\PhpUnit\Tests;

use \tests\PhpUnit\Fixtures\Mock;


class BaseTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test that setting a JSON schema with an invalid JSON string throws an exception.
     */
    public function testSchemaThrowsExceptionWithInvalidJSONString()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('The schema is not a valid JSON string.');
        $base = $this->getMockForAbstractClass('\JsonTable\Base');
        $base->setSchema('This is not a valid JSON string.');
    }


    /**
     * Test that setting an invalid JSON schema throws an exception.
     *
     * @param   mixed $pm_invalid_values    Invalid schema values.
     *
     * @dataProvider    providerInvalidSchemaValues
     */
    public function testSchemaThrowsExceptionWithInvalidValues($invalidValues)
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid schema data type.');
        $base = $this->getMockForAbstractClass('\JsonTable\Base');
        $base->setSchema($invalidValues);
    }


    /**
     * Provider of invalid schema values.
     *
     * @return  array   The invalid schema values.
     */
    public function providerInvalidSchemaValues()
    {
        return [
            [null],
            [1],
            [true]
        ];
    }


    /**
     * Test that setting a valid CSV file path returns true.
     */
    public function testSetValidFilePath()
    {
        $base = $this->getMockForAbstractClass('\JsonTable\Base');
        $setFileReturnValue = $base->setFile('examples/example.csv');
        $this->assertTrue($setFileReturnValue);
    }


    /**
     * Test that setting an invalid file path returns false.
     *
     * @param   mixed   $pm_invalid_values  Invalid file path.
     *
     * @dataProvider    providerInvalidPaths
     */
    public function testSetInvalidFilePath($pm_invalid_values)
    {
        $base = $this->getMockForAbstractClass('\JsonTable\Base');
        $setFileReturnValue = $base->setFile($pm_invalid_values);
        $this->assertFalse($setFileReturnValue);
    }


    /**
     * Provider of invalid file paths.
     *
     * @return  array   The invalid paths.
     */
    public function providerInvalidPaths()
    {
        return [
            ['not a valid path'],
            ['examples/not_valid.csv'],
            [true],
            [null],
            [1],
            [''],
            [' ']
        ];
    }


    /**
     * Test that trying to set an invalid PDO object as a PDO connection returns false.
     *
     * @dataProvider    providerInvalidPdo
     */
    public function testInvalidPdoConnection($invalidPdo)
    {
        $base = $this->getMockForAbstractClass('\JsonTable\Base');
        $this->assertFalse($base->setPdoConnection($invalidPdo));
    }


    /**
     * Provider of invalid file paths.
     *
     * @return  array   The invalid paths.
     */
    public function providerInvalidPdo()
    {
        return [
            ['not a PDO object'],
            [new \stdClass()],
            [true],
            [null],
            [''],
            [' ']
        ];
    }
}