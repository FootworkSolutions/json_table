<?php
namespace tests\PhpUnit\Tests;

use \JsonTable\Analyse\Analyse;
use \tests\PhpUnit\Fixtures\Mock;


class AnalyseTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Set up anything needed by each test.
     */
    public function setUp()
    {
        if (!defined('BASE_DIRECTORY')) {
            define('BASE_DIRECTORY', dirname(dirname(dirname(dirname(dirname(__FILE__))))));
        }
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
     * Get the data from the example schema.
     *
     * @access  private
     *
     * @return  string  The schema as a string.
     */
    private function getExampleSchemaString()
    {
        return file_get_contents(BASE_DIRECTORY . '/examples/example.json');
    }


    /**
     * Test that calling the constructor correctly sets the provided DI properties.
     */
    public function testConstructorSetsProvidedStatisticsProperty()
    {
        $mock = new Mock();
        $statisticsMock = $mock->statistics();
        $analyser = new Analyse($statisticsMock);
        $statistics = $analyser->getStatistics();
        $this->assertEmpty($statistics);
    }


    /**
     * Test that calling the constructor with error DI correctly sets the property.
     */
    public function testConstructorSetsProvidedErrorProperty()
    {
        $mock = new Mock();
        $errorMock = $mock->error();
        $analyser = new Analyse(null, $errorMock);
        $errors = $analyser->getErrors();
        $this->assertEmpty($errors);
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


    /**
     * Test that all valid data returns as valid from the analysis class.
     */
    public function testAnalyseAllValidDataIsReturnedAsValid()
    {
        $mock = new Mock();
        $pdo = $mock->PDO();

        $analyser = new Analyse();
        $analyser->setPdoConnection($pdo);
        $analyser->setSchema($this->getExampleSchemaString());
        $analyser->setFile('examples/example.csv');

        $mock->expectFetchAllResult($pdo, [0 => ['count' => 1]]);

        $fileIsValid = $analyser->validate();
        $this->assertEquals(true, $fileIsValid);
    }


    /**
     * Test that the getting errors returns an empty array when no errors have been set.
     */
    public function testGetEmptyErrorsReturnsEmptyArray()
    {
        $analyser = new Analyse();
        $errors = $analyser->getErrors();
        $this->assertEmpty($errors);
    }


    /**
     * Test that a missing mandatory column is invalid.
     */
    public function testValidateReturnsFalseOnMissingMandatoryColumnInCSVFile()
    {
        $this->createCSVFile(['FIRST_NAME', 'EMAIL_ADDRESS'], [['john', 'test@example.com']]);
        $analyser = new Analyse();
        $analyser->setSchema($this->getExampleSchemaString());
        $analyser->setFile('test.csv');
        $fileIsValid = $analyser->validate();
        $this->assertFalse($fileIsValid);
    }


    /**
     * Test that a missing mandatory column sets the correct error.
     */
    public function testErrorIsSetOnMissingMandatoryColumnInCSVFile()
    {
        $this->createCSVFile(['FIRST_NAME', 'EMAIL_ADDRESS'], [['john', 'test@example.com']]);
        $analyser = new Analyse();
        $analyser->setSchema($this->getExampleSchemaString());
        $analyser->setFile('test.csv');
        $analyser->validate();
        $errors = $analyser->getErrors();
        $expectedError = ['<strong>1</strong> required column(s) missing:' => ['website']];
        $this->assertEquals($expectedError, $errors);
    }


    /**
     * Test that the statistics array has the correct details when there have been no validation errors.
     */
    public function testGetStatisticsWhenNoErrors()
    {
        $mock = new Mock();
        $pdo = $mock->PDO();

        $analyser = new Analyse();
        $analyser->setPdoConnection($pdo);
        $analyser->setSchema($this->getExampleSchemaString());
        $analyser->setFile('examples/example.csv');
        $mock->expectFetchAllResult($pdo, [0 => ['count' => 1]]);

        $analyser->validate();
        $la_statistics = $analyser->getStatistics();
        $la_expected_statistics = [
            'rows_with_errors' => [],
            'percent_rows_with_errors' => 0,
            'rows_analysed' => 2
        ];

        $this->assertEquals($la_expected_statistics, $la_statistics);
    }


    /**
     * Test that the statistics array has the correct details when there is a mandatory column without any data in it.
     */
    public function testStatisticsWhenMandatoryColumnError()
    {
        $mock = new Mock();
        $pdo = $mock->PDO();

        $analyser = new Analyse();
        $analyser->setPdoConnection($pdo);
        $analyser->setSchema($this->getExampleSchemaString());

        $this->createCSVFile(['FIRST_NAME', 'EMAIL_ADDRESS', 'WEBSITE'], [
            ['john', 'test@example.com', ''],
            ['bob', 'something@example.com', 'www.example.com']
        ]);

        $analyser->setFile('test.csv');

        $mock->expectFetchAllResult($pdo, [0 => ['count' => 1]]);

        $analyser->validate();
        $la_statistics = $analyser->getStatistics();
        $la_expected_statistics = [
            'rows_with_errors' => [1],
            'percent_rows_with_errors' => 50,
            'rows_analysed' => 2
        ];

        $this->assertEquals($la_expected_statistics, $la_statistics);
    }


    /**
     * Test that the statistics array has the correct details
     * when there is a column with an invalid email address format.
     */
    public function testStatisticsWhenInvalidEmailFormat()
    {
        $mock = new Mock();
        $pdo = $mock->PDO();

        $analyser = new Analyse();
        $analyser->setPdoConnection($pdo);
        $analyser->setSchema($this->getExampleSchemaString());

        $this->createCSVFile(['FIRST_NAME', 'EMAIL_ADDRESS', 'WEBSITE'], [
            ['john', 'not_an_email_address', 'www.example.com'],
            ['bob', 'something@example.com', 'www.example.com']
        ]);

        $analyser->setFile('test.csv');

        $mock->expectFetchAllResult($pdo, [0 => ['count' => 1]]);

        $analyser->validate();
        $la_statistics = $analyser->getStatistics();
        $la_expected_statistics = [
            'rows_with_errors' => [1],
            'percent_rows_with_errors' => 50,
            'rows_analysed' => 2
        ];

        $this->assertEquals($la_expected_statistics, $la_statistics);
    }


    /**
     * Test that the statistics array has the correct details
     * when there is a column with an invalid website address format.
     */
    public function testStatisticsWhenInvalidWebsiteFormat()
    {
        $mock = new Mock();
        $pdo = $mock->PDO();

        $analyser = new Analyse();
        $analyser->setPdoConnection($pdo);
        $analyser->setSchema($this->getExampleSchemaString());

        $this->createCSVFile(['FIRST_NAME', 'EMAIL_ADDRESS', 'WEBSITE'], [
            ['john', 'john@example.com', 'not_a_website_address'],
            ['bob', 'something@example.com', 'www.example.com']
        ]);

        $analyser->setFile('test.csv');

        $mock->expectFetchAllResult($pdo, [0 => ['count' => 1]]);

        $analyser->validate();
        $la_statistics = $analyser->getStatistics();
        $la_expected_statistics = [
            'rows_with_errors' => [1],
            'percent_rows_with_errors' => 50,
            'rows_analysed' => 2
        ];

        $this->assertEquals($la_expected_statistics, $la_statistics);
    }


    /**
     * Test that the statistics array has the correct details
     * when there is a column with an invalid number format.
     */
    public function testStatisticsWhenInvalidNumberFormat()
    {
        $mock = new Mock();
        $pdo = $mock->PDO();

        $analyser = new Analyse();
        $analyser->setPdoConnection($pdo);
        $analyser->setSchema($this->getExampleSchemaString());

        $this->createCSVFile(['FIRST_NAME', 'EMAIL_ADDRESS', 'WEBSITE', 'HOURS_WORKED_IN_DAY'], [
            ['john', 'john@example.com', 'www.example.com', 'not_a_number'],
            ['bob', 'something@example.com', 'www.example.com', 10.0]
        ]);

        $analyser->setFile('test.csv');

        $mock->expectFetchAllResult($pdo, [0 => ['count' => 1]]);

        $analyser->validate();
        $la_statistics = $analyser->getStatistics();
        $la_expected_statistics = [
            'rows_with_errors' => [1],
            'percent_rows_with_errors' => 50,
            'rows_analysed' => 2
        ];

        $this->assertEquals($la_expected_statistics, $la_statistics);
    }


    /**
     * Test that the statistics array has the correct details
     * when there is a column with an invalid currency format.
     */
    public function testStatisticsWhenInvalidCurrencyFormat()
    {
        $mock = new Mock();
        $pdo = $mock->PDO();

        $analyser = new Analyse();
        $analyser->setPdoConnection($pdo);
        $analyser->setSchema($this->getExampleSchemaString());

        $this->createCSVFile(['FIRST_NAME', 'EMAIL_ADDRESS', 'WEBSITE', 'MONEY_IN_POCKET'], [
            ['john', 'john@example.com', 'www.example.com', 'not_a_currency'],
            ['bob', 'something@example.com', 'www.example.com', '£10.45']
        ]);

        $analyser->setFile('test.csv');

        $mock->expectFetchAllResult($pdo, [0 => ['count' => 1]]);

        $analyser->validate();
        $la_statistics = $analyser->getStatistics();
        $la_expected_statistics = [
            'rows_with_errors' => [1],
            'percent_rows_with_errors' => 50,
            'rows_analysed' => 2
        ];

        $this->assertEquals($la_expected_statistics, $la_statistics);
    }

    /**
     * Test that the statistics array has the correct details
     * when there is a column with an invalid integer format.
     */
    public function testStatisticsWhenInvalidIntegerFormat()
    {
        $mock = new Mock();
        $pdo = $mock->PDO();

        $analyser = new Analyse();
        $analyser->setPdoConnection($pdo);
        $analyser->setSchema($this->getExampleSchemaString());

        $this->createCSVFile(['FIRST_NAME', 'EMAIL_ADDRESS', 'WEBSITE', 'DAYS_SINCE_HAIRCUT'], [
            ['john', 'john@example.com', 'www.example.com', 'not_an_integer'],
            ['bob', 'something@example.com', 'www.example.com', 45]
        ]);

        $analyser->setFile('test.csv');

        $mock->expectFetchAllResult($pdo, [0 => ['count' => 1]]);

        $analyser->validate();
        $la_statistics = $analyser->getStatistics();
        $la_expected_statistics = [
            'rows_with_errors' => [1],
            'percent_rows_with_errors' => 50,
            'rows_analysed' => 2
        ];

        $this->assertEquals($la_expected_statistics, $la_statistics);
    }


    /**
     * Test that the statistics array has the correct details
     * when all the rows analysed have an error
     */
    public function testStatisticsWhenAllRowsAreInvalid()
    {
        $mock = new Mock();
        $pdo = $mock->PDO();

        $analyser = new Analyse();
        $analyser->setPdoConnection($pdo);
        $analyser->setSchema($this->getExampleSchemaString());

        $this->createCSVFile(['FIRST_NAME', 'EMAIL_ADDRESS', 'WEBSITE', 'MONEY_IN_POCKET', 'DAYS_SINCE_HAIRCUT'], [
            ['john', 'john@example.com', 'www.example.com', '$55.99', 'not_an_integer'],
            ['bob', 'something@example.com', 'www.example.com', 'not_a_currency', 45],
            ['bob', '', 'www.example.com', '£34', 300]
        ]);

        $analyser->setFile('test.csv');

        $mock->expectFetchAllResult($pdo, [0 => ['count' => 1]]);

        $analyser->validate();
        $la_statistics = $analyser->getStatistics();
        $la_expected_statistics = [
            'rows_with_errors' => [1, 2, 3],
            'percent_rows_with_errors' => 100,
            'rows_analysed' => 3
        ];

        $this->assertEquals($la_expected_statistics, $la_statistics);
    }


    /**
     * Test that the statistics array has the correct details
     * when there is a column with an invalid boolean format.
     */
    public function testStatisticsWhenInvalidBooleanFormat()
    {
        $mock = new Mock();
        $pdo = $mock->PDO();

        $analyser = new Analyse();
        $analyser->setPdoConnection($pdo);
        $analyser->setSchema($this->getExampleSchemaString());

        $this->createCSVFile(['FIRST_NAME', 'EMAIL_ADDRESS', 'WEBSITE', 'HAS_CAT'], [
            ['john', 'john@example.com', 'www.example.com', true],
            ['bob', 'something@example.com', 'www.example.com', 'not_a_boolean']
        ]);

        $analyser->setFile('test.csv');

        $mock->expectFetchAllResult($pdo, [0 => ['count' => 1]]);

        $analyser->validate();
        $la_statistics = $analyser->getStatistics();
        $la_expected_statistics = [
            'rows_with_errors' => [2],
            'percent_rows_with_errors' => 50,
            'rows_analysed' => 2
        ];

        $this->assertEquals($la_expected_statistics, $la_statistics);
    }


    /**
     * Test that all the allowed boolean values are valid.
     *
     * @dataProvider providerValidBooleanValues
     */
    public function testValidBooleanFormat($booleanValue)
    {
        $mock = new Mock();
        $pdo = $mock->PDO();

        $analyser = new Analyse();
        $analyser->setPdoConnection($pdo);
        $analyser->setSchema($this->getExampleSchemaString());

        $this->createCSVFile(['FIRST_NAME', 'EMAIL_ADDRESS', 'WEBSITE', 'HAS_CAT'], [
            ['john', 'john@example.com', 'www.example.com', $booleanValue]
        ]);

        $analyser->setFile('test.csv');
        $mock->expectFetchAllResult($pdo, [0 => ['count' => 1]]);

        $isValid = $analyser->validate();
        $this->assertEquals(true, $isValid);
    }


    /**
     * Provider of valid boolean values.
     *
     * @return  array   The invalid schema values.
     */
    public function providerValidBooleanValues()
    {
        return [
            [true],
            [false],
            [TRUE],
            [FALSE],
            [1],
            [0],
            ["on"],
            ["off"],
            ["1"],
            ["0"],
            ["YES"],
            ["NO"],
            ["yes"],
            ["no"]
        ];
    }


    /**
     * Test that the statistics array has the correct details
     * when there is a column with an invalid null format.
     */
    public function testStatisticsWhenInvalidNullFormat()
    {
        $mock = new Mock();
        $pdo = $mock->PDO();

        $analyser = new Analyse();
        $analyser->setPdoConnection($pdo);
        $analyser->setSchema($this->getExampleSchemaString());

        $this->createCSVFile(['FIRST_NAME', 'EMAIL_ADDRESS', 'WEBSITE', 'DONT_USE'], [
            ['john', 'john@example.com', 'www.example.com', null],
            ['bob', 'something@example.com', 'www.example.com', 'not_null']
        ]);

        $analyser->setFile('test.csv');

        $mock->expectFetchAllResult($pdo, [0 => ['count' => 1]]);

        $analyser->validate();
        $la_statistics = $analyser->getStatistics();
        $la_expected_statistics = [
            'rows_with_errors' => [2],
            'percent_rows_with_errors' => 50,
            'rows_analysed' => 2
        ];

        $this->assertEquals($la_expected_statistics, $la_statistics);
    }


    /**
     * Test that all the allowed null values are valid.
     *
     * @dataProvider providerValidNullValues
     */
    public function testValidNullFormat($nullValue)
    {
        $mock = new Mock();
        $pdo = $mock->PDO();

        $analyser = new Analyse();
        $analyser->setPdoConnection($pdo);
        $analyser->setSchema($this->getExampleSchemaString());

        $this->createCSVFile(['FIRST_NAME', 'EMAIL_ADDRESS', 'WEBSITE', 'DONT_USE'], [
            ['john', 'john@example.com', 'www.example.com', $nullValue]
        ]);

        $analyser->setFile('test.csv');
        $mock->expectFetchAllResult($pdo, [0 => ['count' => 1]]);

        $isValid = $analyser->validate();
        $this->assertEquals(true, $isValid);
    }


    /**
     * Provider of valid null values.
     *
     * @return  array   The invalid schema values.
     */
    public function providerValidNullValues()
    {
        return [
            [''],
            ["\N"],
            [null]
        ];
    }
}
