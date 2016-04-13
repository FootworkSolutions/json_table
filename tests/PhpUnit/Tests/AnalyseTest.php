<?php
namespace tests\PhpUnit\Tests;

use \JsonTable\Analyse\Analyse;
use \tests\PhpUnit\Fixtures\Mock;

class AnalyseTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Create a test CSV file with the specified headers and field data.
     * The file that is created is named "test.csv" and is in the current directory.
     *
     * @access private
     *
     * @param array $pa_column_names The headers.
     * @param array $pa_field_values The field values as a multi-dimensional array.
     *
     * @return void
     */
    private function createCSVFile($columnNames, $fieldValues)
    {
        $file = fopen('test.csv', 'w');

        fputcsv($file, $columnNames);

        foreach ($fieldValues as $rowValues) {
            fputcsv($file, $rowValues);
        }

        fclose($file);
    }


    /**
     * Remove any test files that have been created during the testing process.
     */
    protected function tearDown()
    {
        if (file_exists('test.csv')) {
            unlink('test.csv');
        }
    }


    /**
     * Test that setting a JSON schema with an invalid JSON string throws an exception.
     */
    public function testSchemaThrowsExceptionWithInvalidJSONString()
    {
        $this->expectException(\Exception::class);
        $analyser = new Analyse();
        $analyser->setSchema('This is not a valid JSON string.');
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
        $analyser = new Analyse();
        $analyser->setSchema($invalidValues);
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
        $analyser = new Analyse();
        $setFileReturnValue = $analyser->setFile('examples/example.csv');
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
        $analyser = new Analyse();
        $setFileReturnValue = $analyser->setFile($pm_invalid_values);
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
            [1]
        ];
    }


    public function testAnalyseAllValidDataIsReturnedAsValid()
    {
        $mock = new Mock();
        $pdo = $mock->PDO();

        $analyser = new Analyse();
        $analyser->setPdoConnection($pdo);
        $analyser->setSchema(file_get_contents('examples/example.json'));
        $analyser->setFile('examples/example.csv');

        $mock->expectFetchAllResult($pdo, [0 => ['count' => 1]]);

        $fileIsValid = $analyser->validate();
        $this->assertEquals(true, $fileIsValid);
    }


    public function testGetEmptyErrorsReturnsEmptyArray()
    {
        $analyser = new Analyse();
        $errors = $analyser->getErrors();
        $this->assertEmpty($errors);
    }


    public function testAValidateReturnsFalseOnMissingMandatoryColumnInCSVFile()
    {
        // Create a test CSV file with a missing mandatory "WEBSITE" column.
        $this->createCSVFile(['FIRST_NAME', 'EMAIL_ADDRESS'], [['john', 'test@example.com']]);
        $analyser = new Analyse();
        $analyser->setSchema(file_get_contents('examples/example.json'));
        $analyser->setFile('test.csv');
        $fileIsValid = $analyser->validate();

        $this->assertFalse($fileIsValid);
    }


    public function testErrorIsSetOnMissingMandatoryColumnInCSVFile()
    {
        // Create a test CSV file with a missing mandatory "WEBSITE" column.
        $this->createCSVFile(['FIRST_NAME', 'EMAIL_ADDRESS'], [['john', 'test@example.com']]);
        $analyser = new Analyse();
        $analyser->setSchema(file_get_contents('examples/example.json'));
        $analyser->setFile('test.csv');
        $analyser->validate();
        $errors = $analyser->getErrors();

        $expectedError = ['<strong>1</strong> required column(s) missing:' => ['website']];
        $this->assertEquals($expectedError, $errors);
    }


//    public function testGetStatisticsWhenNoErrors()
//    {
//        $mock = new Mock();
//        $pdo = $mock->PDO();
//
//        $analyser = new Analyse();
//        $analyser->setPdoConnection($pdo);
//        $analyser->setSchema(file_get_contents('examples/example.json'));
//        $analyser->setFile('examples/example.csv');
//        $pdo->expects($this->once())
//            ->method('bindParam')
//            ->will($this->returnValue(false));

//        $analyser->validate();
//        $la_statistics = $analyser->getStatistics();
//
//        $la_expected_statistics = [
//            'rows_with_errors' => [],
//            'percent_rows_with_errors' => 0,
//            'rows_analysed' => 3
//        ];
//
//        $this->assertEquals($la_expected_statistics, $la_statistics);
//    }
}
