<?php
namespace tests\PhpUnit\Tests;

use \JsonTable\Analyse\Analyse;
use \tests\PhpUnit\Fixtures\Mock;
use \tests\PhpUnit\Fixtures\Helper;


class AnalyseTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Remove any test files that have been created during the testing process.
     */
    protected function tearDown()
    {
        Helper::deleteTestCSVFile();
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
     * Test that all valid data returns as valid from the analysis class.
     */
    public function testAnalyseAllValidDataIsReturnedAsValid()
    {
        $mock = new Mock();
        $pdo = $mock->PDO();

        $analyser = new Analyse();
        $analyser->setPdoConnection($pdo);
        $analyser->setSchema(Helper::getExampleSchemaString());
        $analyser->setFile(Helper::getExampleCSVLocation());

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
        Helper::createTestCSVFile(['FIRST_NAME', 'EMAIL_ADDRESS'], [['john', 'test@example.com']]);
        $analyser = new Analyse();
        $analyser->setSchema(Helper::getExampleSchemaString());
        $analyser->setFile(Helper::getTestCSVFile());
        $fileIsValid = $analyser->validate();
        $this->assertFalse($fileIsValid);
    }


    /**
     * Test that a missing mandatory column sets the correct error.
     */
    public function testErrorIsSetOnMissingMandatoryColumnInCSVFile()
    {
        Helper::createTestCSVFile(['FIRST_NAME', 'EMAIL_ADDRESS'], [['john', 'test@example.com']]);
        $analyser = new Analyse();
        $analyser->setSchema(Helper::getExampleSchemaString());
        $analyser->setFile(Helper::getTestCSVFile());
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
        $analyser->setSchema(Helper::getExampleSchemaString());
        $analyser->setFile(Helper::getExampleCSVLocation());
        $mock->expectFetchAllResult($pdo, [0 => ['count' => 1]]);

        $analyser->validate();
        $statistics = $analyser->getStatistics();
        $expectedStatistics = [
            'rows_with_errors' => [],
            'percent_rows_with_errors' => 0,
            'rows_analysed' => 2
        ];

        $this->assertEquals($expectedStatistics, $statistics);
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
        $analyser->setSchema(Helper::getExampleSchemaString());

        Helper::createTestCSVFile(['FIRST_NAME', 'EMAIL_ADDRESS', 'WEBSITE'], [
            ['john', 'test@example.com', ''],
            ['bob', 'something@example.com', 'www.example.com']
        ]);

        $analyser->setFile(Helper::getTestCSVFile());

        $mock->expectFetchAllResult($pdo, [0 => ['count' => 1]]);

        $analyser->validate();
        $statistics = $analyser->getStatistics();
        $expectedStatistics = [
            'rows_with_errors' => [1],
            'percent_rows_with_errors' => 50,
            'rows_analysed' => 2
        ];

        $this->assertEquals($expectedStatistics, $statistics);
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
        $analyser->setSchema(Helper::getExampleSchemaString());

        Helper::createTestCSVFile(['FIRST_NAME', 'EMAIL_ADDRESS', 'WEBSITE'], [
            ['john', 'not_an_email_address', 'www.example.com'],
            ['bob', 'something@example.com', 'www.example.com']
        ]);

        $analyser->setFile(Helper::getTestCSVFile());

        $mock->expectFetchAllResult($pdo, [0 => ['count' => 1]]);

        $analyser->validate();
        $statistics = $analyser->getStatistics();
        $expectedStatistics = [
            'rows_with_errors' => [1],
            'percent_rows_with_errors' => 50,
            'rows_analysed' => 2
        ];

        $this->assertEquals($expectedStatistics, $statistics);
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
        $analyser->setSchema(Helper::getExampleSchemaString());

        Helper::createTestCSVFile(['FIRST_NAME', 'EMAIL_ADDRESS', 'WEBSITE'], [
            ['john', 'john@example.com', 'not_a_website_address'],
            ['bob', 'something@example.com', 'www.example.com']
        ]);

        $analyser->setFile(Helper::getTestCSVFile());

        $mock->expectFetchAllResult($pdo, [0 => ['count' => 1]]);

        $analyser->validate();
        $statistics = $analyser->getStatistics();
        $expectedStatistics = [
            'rows_with_errors' => [1],
            'percent_rows_with_errors' => 50,
            'rows_analysed' => 2
        ];

        $this->assertEquals($expectedStatistics, $statistics);
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
        $analyser->setSchema(Helper::getExampleSchemaString());

        Helper::createTestCSVFile(['FIRST_NAME', 'EMAIL_ADDRESS', 'WEBSITE', 'HOURS_WORKED_IN_DAY'], [
            ['john', 'john@example.com', 'www.example.com', 'not_a_number'],
            ['bob', 'something@example.com', 'www.example.com', 10.0]
        ]);

        $analyser->setFile(Helper::getTestCSVFile());

        $mock->expectFetchAllResult($pdo, [0 => ['count' => 1]]);

        $analyser->validate();
        $statistics = $analyser->getStatistics();
        $expectedStatistics = [
            'rows_with_errors' => [1],
            'percent_rows_with_errors' => 50,
            'rows_analysed' => 2
        ];

        $this->assertEquals($expectedStatistics, $statistics);
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
        $analyser->setSchema(Helper::getExampleSchemaString());

        Helper::createTestCSVFile(['FIRST_NAME', 'EMAIL_ADDRESS', 'WEBSITE', 'MONEY_IN_POCKET'], [
            ['john', 'john@example.com', 'www.example.com', 'not_a_currency'],
            ['bob', 'something@example.com', 'www.example.com', '£10.45']
        ]);

        $analyser->setFile(Helper::getTestCSVFile());

        $mock->expectFetchAllResult($pdo, [0 => ['count' => 1]]);

        $analyser->validate();
        $statistics = $analyser->getStatistics();
        $expectedStatistics = [
            'rows_with_errors' => [1],
            'percent_rows_with_errors' => 50,
            'rows_analysed' => 2
        ];

        $this->assertEquals($expectedStatistics, $statistics);
    }


    /**
     * Test that the statistics array has the correct details
     * when there is a valid empty number.
     */
    public function testStatisticsWhenEmptyNumberFormat()
    {
        $mock = new Mock();
        $pdo = $mock->PDO();

        $analyser = new Analyse();
        $analyser->setPdoConnection($pdo);
        $analyser->setSchema(Helper::getExampleSchemaString());

        Helper::createTestCSVFile(['FIRST_NAME', 'EMAIL_ADDRESS', 'WEBSITE', 'MONEY_IN_POCKET'], [
            ['john', 'john@example.com', 'www.example.com', '']
        ]);

        $analyser->setFile(Helper::getTestCSVFile());

        $mock->expectFetchAllResult($pdo, [0 => ['count' => 1]]);

        $analyser->validate();
        $statistics = $analyser->getStatistics();
        $expectedStatistics = [
            'rows_with_errors' => [],
            'percent_rows_with_errors' => 0,
            'rows_analysed' => 1
        ];

        $this->assertEquals($expectedStatistics, $statistics);
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
        $analyser->setSchema(Helper::getExampleSchemaString());

        Helper::createTestCSVFile(['FIRST_NAME', 'EMAIL_ADDRESS', 'WEBSITE', 'DAYS_SINCE_HAIRCUT'], [
            ['john', 'john@example.com', 'www.example.com', 'not_an_integer'],
            ['bob', 'something@example.com', 'www.example.com', 45]
        ]);

        $analyser->setFile(Helper::getTestCSVFile());

        $mock->expectFetchAllResult($pdo, [0 => ['count' => 1]]);

        $analyser->validate();
        $statistics = $analyser->getStatistics();
        $expectedStatistics = [
            'rows_with_errors' => [1],
            'percent_rows_with_errors' => 50,
            'rows_analysed' => 2
        ];

        $this->assertEquals($expectedStatistics, $statistics);
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
        $analyser->setSchema(Helper::getExampleSchemaString());

        Helper::createTestCSVFile(['FIRST_NAME', 'EMAIL_ADDRESS', 'WEBSITE', 'MONEY_IN_POCKET', 'DAYS_SINCE_HAIRCUT'], [
            ['john', 'john@example.com', 'www.example.com', '$55.99', 'not_an_integer'],
            ['bob', 'something@example.com', 'www.example.com', 'not_a_currency', 45],
            ['bob', '', 'www.example.com', '£34', 300]
        ]);

        $analyser->setFile(Helper::getTestCSVFile());

        $mock->expectFetchAllResult($pdo, [0 => ['count' => 1]]);

        $analyser->validate();
        $statistics = $analyser->getStatistics();
        $expectedStatistics = [
            'rows_with_errors' => [1, 2, 3],
            'percent_rows_with_errors' => 100,
            'rows_analysed' => 3
        ];

        $this->assertEquals($expectedStatistics, $statistics);
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
        $analyser->setSchema(Helper::getExampleSchemaString());

        Helper::createTestCSVFile(['FIRST_NAME', 'EMAIL_ADDRESS', 'WEBSITE', 'HAS_CAT'], [
            ['john', 'john@example.com', 'www.example.com', true],
            ['bob', 'something@example.com', 'www.example.com', 'not_a_boolean']
        ]);

        $analyser->setFile(Helper::getTestCSVFile());

        $mock->expectFetchAllResult($pdo, [0 => ['count' => 1]]);

        $analyser->validate();
        $statistics = $analyser->getStatistics();
        $expectedStatistics = [
            'rows_with_errors' => [2],
            'percent_rows_with_errors' => 50,
            'rows_analysed' => 2
        ];

        $this->assertEquals($expectedStatistics, $statistics);
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
        $analyser->setSchema(Helper::getExampleSchemaString());

        Helper::createTestCSVFile(['FIRST_NAME', 'EMAIL_ADDRESS', 'WEBSITE', 'HAS_CAT'], [
            ['john', 'john@example.com', 'www.example.com', $booleanValue]
        ]);

        $analyser->setFile(Helper::getTestCSVFile());
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
        $analyser->setSchema(Helper::getExampleSchemaString());

        Helper::createTestCSVFile(['FIRST_NAME', 'EMAIL_ADDRESS', 'WEBSITE', 'DONT_USE'], [
            ['john', 'john@example.com', 'www.example.com', null],
            ['bob', 'something@example.com', 'www.example.com', 'not_null']
        ]);

        $analyser->setFile(Helper::getTestCSVFile());

        $mock->expectFetchAllResult($pdo, [0 => ['count' => 1]]);

        $analyser->validate();
        $statistics = $analyser->getStatistics();
        $expectedStatistics = [
            'rows_with_errors' => [2],
            'percent_rows_with_errors' => 50,
            'rows_analysed' => 2
        ];

        $this->assertEquals($expectedStatistics, $statistics);
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
        $analyser->setSchema(Helper::getExampleSchemaString());

        Helper::createTestCSVFile(['FIRST_NAME', 'EMAIL_ADDRESS', 'WEBSITE', 'DONT_USE'], [
            ['john', 'john@example.com', 'www.example.com', $nullValue]
        ]);

        $analyser->setFile(Helper::getTestCSVFile());
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


    /**
     * Test that the statistics array has the correct details
     * when there is a column with a valid date format.
     */
    public function testStatisticsWhenValidDateFormat()
    {
        $mock = new Mock();
        $pdo = $mock->PDO();

        $analyser = new Analyse();
        $analyser->setPdoConnection($pdo);
        $analyser->setSchema(Helper::getExampleSchemaString());

        Helper::createTestCSVFile(['FIRST_NAME', 'EMAIL_ADDRESS', 'WEBSITE', 'DATE_OF_BIRTH'], [
            ['john', 'john@example.com', 'www.example.com', '1980s-09-26'],
            ['bob', 'something@example.com', 'www.example.com', '2000-02-20']
        ]);

        $analyser->setFile(Helper::getTestCSVFile());

        $mock->expectFetchAllResult($pdo, [0 => ['count' => 1]]);

        $analyser->validate();
        $statistics = $analyser->getStatistics();
        $expectedStatistics = [
            'rows_with_errors' => [1],
            'percent_rows_with_errors' => 50,
            'rows_analysed' => 2
        ];

        $this->assertEquals($expectedStatistics, $statistics);
    }


    /**
     * Test that the statistics array has the correct details
     * when there is a column with an invalid date format.
     *
     * @dataProvider providerInvalidDateValues
     */
    public function testStatisticsWhenInvalidDateFormat($invalidDate)
    {
        $mock = new Mock();
        $pdo = $mock->PDO();

        $analyser = new Analyse();
        $analyser->setPdoConnection($pdo);
        $analyser->setSchema(Helper::getExampleSchemaString());

        Helper::createTestCSVFile(['FIRST_NAME', 'EMAIL_ADDRESS', 'WEBSITE', 'DATE_OF_BIRTH'], [
            ['john', 'john@example.com', 'www.example.com', $invalidDate]
        ]);

        $analyser->setFile(Helper::getTestCSVFile());

        $mock->expectFetchAllResult($pdo, [0 => ['count' => 1]]);

        $analyser->validate();
        $statistics = $analyser->getStatistics();
        $expectedStatistics = [
            'rows_with_errors' => [1],
            'percent_rows_with_errors' => 100,
            'rows_analysed' => 1
        ];

        $this->assertEquals($expectedStatistics, $statistics);
    }


    /**
     * Provider of invalid date values.
     *
     * @return  array   The invalid date values.
     */
    public function providerInvalidDateValues()
    {
        return [
            [' '],
            ['20000-01-01'],
            ['not_a_date'],
            ['0000000'],
            ['0000-00-00'],
            ['abcd-ab-cd'],
            ['1990-01-40']
        ];
    }


    /**
     * Test that trying to validate an a format that doesn't exist throws an exception.
     */
    public function testInvalidFormatThrowsException()
    {
        $mock = new Mock();
        $pdo = $mock->PDO();

        $analyser = new Analyse();
        $analyser->setPdoConnection($pdo);
        $analyser->setSchema('{
            "fields": [{
                "name": "FIRST_NAME",
                "title": "First Name",
                "description": "The user\'s first name",
                "type": "not_a_format"
            }]}'
        );

        Helper::createTestCSVFile(['FIRST_NAME'], [['john']]);
        $analyser->setFile(Helper::getTestCSVFile());

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Could not load the validator file for Format not_a_format.');

        $analyser->validate();
    }


    /**
     * Test that trying to validate without setting a CSV file throws an exception.
     */
    public function testRunningValidateWithoutCSVCausesError()
    {
        $mock = new Mock();
        $pdo = $mock->PDO();

        $analyser = new Analyse();
        $analyser->setPdoConnection($pdo);
        $analyser->setSchema(Helper::getExampleSchemaString());

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('CSV file not set.');
        $analyser->validate();
    }


    /**
     * Test that the statistics array has the correct details
     * when there is a column with a valid UK date format.
     */
    public function testStatisticsWhenValidUKDateFormat()
    {
        $mock = new Mock();
        $pdo = $mock->PDO();

        $analyser = new Analyse();
        $analyser->setPdoConnection($pdo);
        $analyser->setSchema(Helper::getExampleSchemaString());

        Helper::createTestCSVFile(['FIRST_NAME', 'EMAIL_ADDRESS', 'WEBSITE', 'DATE_OF_BIRTH_UK'], [
            ['john', 'john@example.com', 'www.example.com', '26/09/1977'],
            ['bob', 'something@example.com', 'www.example.com', '30/03/1999']
        ]);

        $analyser->setFile(Helper::getTestCSVFile());

        $mock->expectFetchAllResult($pdo, [0 => ['count' => 1]]);

        $analyser->validate();
        $statistics = $analyser->getStatistics();
        $expectedStatistics = [
            'rows_with_errors' => [],
            'percent_rows_with_errors' => 0,
            'rows_analysed' => 2
        ];

        $this->assertEquals($expectedStatistics, $statistics);
    }


    /**
     * Test that the statistics array has the correct details
     * when there is a column with an invalid UK date format.
     *
     * @dataProvider providerInvalidDateValues
     */
    public function testStatisticsWhenInvalidUKDateFormat($invalidDate)
    {
        $mock = new Mock();
        $pdo = $mock->PDO();

        $analyser = new Analyse();
        $analyser->setPdoConnection($pdo);
        $analyser->setSchema(Helper::getExampleSchemaString());

        Helper::createTestCSVFile(['FIRST_NAME', 'EMAIL_ADDRESS', 'WEBSITE', 'DATE_OF_BIRTH_UK'], [
            ['john', 'john@example.com', 'www.example.com', $invalidDate]
        ]);

        $analyser->setFile(Helper::getTestCSVFile());

        $mock->expectFetchAllResult($pdo, [0 => ['count' => 1]]);

        $analyser->validate();
        $statistics = $analyser->getStatistics();
        $expectedStatistics = [
            'rows_with_errors' => [1],
            'percent_rows_with_errors' => 100,
            'rows_analysed' => 1
        ];

        $this->assertEquals($expectedStatistics, $statistics);
    }


    /**
     * Test that the statistics array has the correct details
     * when there is a column with a valid datetime format.
     */
    public function testStatisticsWhenValidDateTimeFormat()
    {
        $mock = new Mock();
        $pdo = $mock->PDO();

        $analyser = new Analyse();
        $analyser->setPdoConnection($pdo);
        $analyser->setSchema(Helper::getExampleSchemaString());

        Helper::createTestCSVFile(['FIRST_NAME', 'EMAIL_ADDRESS', 'WEBSITE', 'LAST_LOGIN'], [
            ['john', 'john@example.com', 'www.example.com', '2016-01-01 10:43:45'],
            ['bob', 'something@example.com', 'www.example.com', '2015-10-13 15:13:25']
        ]);

        $analyser->setFile(Helper::getTestCSVFile());

        $mock->expectFetchAllResult($pdo, [0 => ['count' => 1]]);

        $analyser->validate();
        $statistics = $analyser->getStatistics();
        $expectedStatistics = [
            'rows_with_errors' => [],
            'percent_rows_with_errors' => 0,
            'rows_analysed' => 2
        ];

        $this->assertEquals($expectedStatistics, $statistics);
    }


    /**
     * Test that the statistics array has the correct details
     * when there is a column with an invalid datetime format.
     *
     * @dataProvider providerInvalidDateValues
     */
    public function testStatisticsWhenInvalidDateTimeFormat($invalidDate)
    {
        $mock = new Mock();
        $pdo = $mock->PDO();

        $analyser = new Analyse();
        $analyser->setPdoConnection($pdo);
        $analyser->setSchema(Helper::getExampleSchemaString());

        Helper::createTestCSVFile(['FIRST_NAME', 'EMAIL_ADDRESS', 'WEBSITE', 'LAST_LOGIN'], [
            ['john', 'john@example.com', 'www.example.com', $invalidDate]
        ]);

        $analyser->setFile(Helper::getTestCSVFile());

        $mock->expectFetchAllResult($pdo, [0 => ['count' => 1]]);

        $analyser->validate();
        $statistics = $analyser->getStatistics();
        $expectedStatistics = [
            'rows_with_errors' => [1],
            'percent_rows_with_errors' => 100,
            'rows_analysed' => 1
        ];

        $this->assertEquals($expectedStatistics, $statistics);
    }


    /**
     * Test that the statistics array has the correct details
     * when there is a column with a valid time format.
     */
    public function testStatisticsWhenValidTimeFormat()
    {
        $mock = new Mock();
        $pdo = $mock->PDO();

        $analyser = new Analyse();
        $analyser->setPdoConnection($pdo);
        $analyser->setSchema(Helper::getExampleSchemaString());

        Helper::createTestCSVFile(['FIRST_NAME', 'EMAIL_ADDRESS', 'WEBSITE', 'BREAKFAST_TIME'], [
            ['john', 'john@example.com', 'www.example.com', '10:43:45'],
            ['bob', 'something@example.com', 'www.example.com', '15:13:25']
        ]);

        $analyser->setFile(Helper::getTestCSVFile());

        $mock->expectFetchAllResult($pdo, [0 => ['count' => 1]]);

        $analyser->validate();
        $statistics = $analyser->getStatistics();
        $expectedStatistics = [
            'rows_with_errors' => [],
            'percent_rows_with_errors' => 0,
            'rows_analysed' => 2
        ];

        $this->assertEquals($expectedStatistics, $statistics);
    }


    /**
     * Test that the statistics array has the correct details
     * when there is a column with an invalid time format.
     *
     * @dataProvider providerInvalidDateValues
     */
    public function testStatisticsWhenInvalidTimeFormat($invalidDate)
    {
        $mock = new Mock();
        $pdo = $mock->PDO();

        $analyser = new Analyse();
        $analyser->setPdoConnection($pdo);
        $analyser->setSchema(Helper::getExampleSchemaString());

        Helper::createTestCSVFile(['FIRST_NAME', 'EMAIL_ADDRESS', 'WEBSITE', 'BREAKFAST_TIME'], [
            ['john', 'john@example.com', 'www.example.com', $invalidDate]
        ]);

        $analyser->setFile(Helper::getTestCSVFile());

        $mock->expectFetchAllResult($pdo, [0 => ['count' => 1]]);

        $analyser->validate();
        $statistics = $analyser->getStatistics();
        $expectedStatistics = [
            'rows_with_errors' => [1],
            'percent_rows_with_errors' => 100,
            'rows_analysed' => 1
        ];

        $this->assertEquals($expectedStatistics, $statistics);
    }


    /**
     * Test that the statistics array has the correct details
     * when there is a column with a valid "any" format.
     */
    public function testStatisticsWhenValidAnyFormat()
    {
        $mock = new Mock();
        $pdo = $mock->PDO();

        $analyser = new Analyse();
        $analyser->setPdoConnection($pdo);
        $analyser->setSchema(Helper::getExampleSchemaString());

        Helper::createTestCSVFile(['FIRST_NAME', 'EMAIL_ADDRESS', 'WEBSITE', 'DATA_DUMP'], [
            ['john', 'john@example.com', 'www.example.com', 'Any data here'],
            ['bob', 'something@example.com', 'www.example.com', '15:13:25']
        ]);

        $analyser->setFile(Helper::getTestCSVFile());

        $mock->expectFetchAllResult($pdo, [0 => ['count' => 1]]);

        $analyser->validate();
        $statistics = $analyser->getStatistics();
        $expectedStatistics = [
            'rows_with_errors' => [],
            'percent_rows_with_errors' => 0,
            'rows_analysed' => 2
        ];

        $this->assertEquals($expectedStatistics, $statistics);
    }


    /**
     * Test that the statistics array has the correct details
     * when there is a column with a valid string format with a specified pattern
     */
    public function testStatisticsWhenValidStringWithPattern()
    {
        $mock = new Mock();
        $pdo = $mock->PDO();

        $analyser = new Analyse();
        $analyser->setPdoConnection($pdo);
        $analyser->setSchema(Helper::getExampleSchemaString());

        Helper::createTestCSVFile(['FIRST_NAME', 'EMAIL_ADDRESS', 'WEBSITE', 'MOOD'], [
            ['john', 'john@example.com', 'www.example.com', 'HAPPY'],
            ['bob', 'something@example.com', 'www.example.com', 'SAD']
        ]);

        $analyser->setFile(Helper::getTestCSVFile());

        $mock->expectFetchAllResult($pdo, [0 => ['count' => 1]]);

        $analyser->validate();
        $statistics = $analyser->getStatistics();
        $expectedStatistics = [
            'rows_with_errors' => [],
            'percent_rows_with_errors' => 0,
            'rows_analysed' => 2
        ];

        $this->assertEquals($expectedStatistics, $statistics);
    }


    /**
     * Test that the statistics array has the correct details
     * when there is a column with a valid string format with a specified pattern
     *
     * @dataProvider    providerInvalidPatternValues
     */
    public function testStatisticsWhenInvalidStringWithPattern($invalidDate)
    {
        $mock = new Mock();
        $pdo = $mock->PDO();

        $analyser = new Analyse();
        $analyser->setPdoConnection($pdo);
        $analyser->setSchema(Helper::getExampleSchemaString());

        Helper::createTestCSVFile(['FIRST_NAME', 'EMAIL_ADDRESS', 'WEBSITE', 'MOOD'], [
            ['john', 'john@example.com', 'www.example.com', '$invalidDate']
        ]);

        $analyser->setFile(Helper::getTestCSVFile());

        $mock->expectFetchAllResult($pdo, [0 => ['count' => 1]]);

        $analyser->validate();
        $statistics = $analyser->getStatistics();
        $expectedStatistics = [
            'rows_with_errors' => [1],
            'percent_rows_with_errors' => 100,
            'rows_analysed' => 1
        ];

        $this->assertEquals($expectedStatistics, $statistics);
    }


    /**
     * Provider of invalid date values.
     *
     * @return  array   The invalid date values.
     */
    public function providerInvalidPatternValues()
    {
        return [
            [''],
            [' '],
            ['not_a_valid_pattern'],
            ['0000000'],
            ['sads'],
            ['indiffererernt'],
            ['1990-01-40']
        ];
    }


    /**
     * Test that the statistics array has the correct details
     * when a foreign key isn't found.
     */
    public function testStatisticsOnUnmatchedForeignKey()
    {
        $mock = new Mock();
        $pdo = $mock->PDO();

        $analyser = new Analyse();
        $analyser->setPdoConnection($pdo);
        $analyser->setSchema(Helper::getExampleSchemaString());
        $analyser->setFile(Helper::getExampleCSVLocation());

        $mock->expectFetchAllResult($pdo, [0 => ['count' => 0]]);

        $analyser->validate();
        $statistics = $analyser->getStatistics();
        $expectedStatistics = [
            'rows_with_errors' => [1, 2],
            'percent_rows_with_errors' => 100,
            'rows_analysed' => 2
        ];

        $this->assertEquals($expectedStatistics, $statistics);
    }


    /**
     * Test that the statistics array has the correct details
     * when a row has an unexpected column count.
     */
    public function testStatisticsWhenRowHasUnexpectedColumnCount()
    {
        $mock = new Mock();
        $pdo = $mock->PDO();

        $analyser = new Analyse();
        $analyser->setPdoConnection($pdo);
        $analyser->setSchema(Helper::getExampleSchemaString());

        Helper::createTestCSVFile(['FIRST_NAME', 'EMAIL_ADDRESS', 'WEBSITE', 'MOOD'], [
            ['john', 'john@example.com', 'www.example.com']
        ]);

        $analyser->setFile(Helper::getTestCSVFile());
        $mock->expectFetchAllResult($pdo, [0 => ['count' => 1]]);
        $analyser->validate();
        $statistics = $analyser->getStatistics();
        $expectedStatistics = [
            'rows_with_errors' => [1],
            'percent_rows_with_errors' => 100,
            'rows_analysed' => 1
        ];

        $this->assertEquals($expectedStatistics, $statistics);
    }
}
