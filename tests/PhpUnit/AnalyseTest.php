<?php
namespace tests\PhpUnit;

use \JsonTable\Analyse;

class AnalyseTest extends \PHPUnit_Extensions_Database_TestCase
{
	/**
	 * @var string Database connection string.
	 */
	const DB_CONNECTION_STRING = 'pgsql:host=localhost;dbname=test1;user=postgres';


	/**
	 * @return PHPUnit_Extensions_Database_DB_IDatabaseConnection
	 */
	public function getConnection()
	{
		$lo_pdo = new \PDO(self::DB_CONNECTION_STRING);
		return $this->createDefaultDBConnection($lo_pdo, 'travis_ci_test');
	}


	/**
	 * @return PHPUnit_Extensions_Database_DataSet_DefaultDataSet
	 */
	public function getDataSet()
	{
		//TODO: WOrk out how to get PHPunit to create the tables before starting the tests.
		return new \PHPUnit_Extensions_Database_DataSet_DefaultDataSet();
	}


	/**
	 * Create a test CSV file with the specified headers and field data.
	 * The file that is created is named "test.csv" and is in the current directory.
	 *
	 * @access public
	 *
	 * @param array $pa_column_names The headers.
	 * @param array $pa_field_values The field values as a multimimentional array.
	 *
	 * @return void
	 */
	private function _createCSVFile($pa_column_names, $pa_field_values)
	{
		$lr_file = fopen('test.csv', 'w');

		fputcsv($lr_file, $pa_column_names);

		foreach ($pa_field_values as $pa_row_values) {
			fputcsv($lr_file, $pa_row_values);
		}

		fclose($lr_file);
	}


	protected function tearDown()
	{
		// Remove any test files that have been created.
		if (file_exists('test.csv')) {
			unlink('test.csv');
		}
	}


	public function testSchemaThrowsExceptionWithInvalidJSONString()
	{
		$this->setExpectedException('Exception', 'The schema is not a valid JSON string.');
		$lo_analyser = new Analyse();
		$lo_analyser->set_schema('This is not a valid JSON string.');
	}


	/**
	 * @param mixed $pm_invalid_values Invalid schema values.
	 *
	 * @dataProvider providerInvalidSchemaValues
	 */
	public function testSchemaThrowsExceptionWithInvalidValues($pm_invalid_values)
	{
		$this->setExpectedException('Exception', 'Invalid schema data type.');
		$lo_analyser = new Analyse();
		$lo_analyser->set_schema($pm_invalid_values);
	}


	public function providerInvalidSchemaValues()
	{
		return [
			[null],
			[1],
			[true]
		];
	}


	public function testSetValidPDOConnection()
	{
		$lo_pdo = new \PDO(self::DB_CONNECTION_STRING);
		$lo_analyser = new Analyse();
		$lb_connection_return_value = $lo_analyser->set_pdo_connection($lo_pdo);
		$this->assertTrue($lb_connection_return_value);
	}


	/**
	 * @param mixed $pm_invalid_values Invalid PDO objects.
	 *
	 * @dataProvider providerInvalidPDOCOnnectionValues
	 */
	public function testSetInvalidPDOConnection($pm_invalid_values)
	{
		$lo_analyser = new Analyse();
		$lb_connection_return_value = $lo_analyser->set_pdo_connection($pm_invalid_values);
		$this->assertFalse($lb_connection_return_value);
	}


	public function providerInvalidPDOCOnnectionValues()
	{
		return [
			['this is not a valid PDO connection'],
			[new \stdClass()],
			[null],
			[''],
			[true]
		];
	}


	public function testSetValidFilePath()
	{
		$lo_analyser = new Analyse();
		$lb_set_file_return_value = $lo_analyser->set_file('examples/example.csv');
		$this->assertTrue($lb_set_file_return_value);
	}


	/**
	 * @param mixed $pm_invalid_values Invalid file path.
	 *
	 * @dataProvider providerInvalidPathStrings
	 */
	public function testSetInvalidFilePath($pm_invalid_values)
	{
		$lo_analyser = new Analyse();
		$lb_set_file_return_value = $lo_analyser->set_file($pm_invalid_values);
		$this->assertFalse($lb_set_file_return_value);
	}


	public function providerInvalidPathStrings()
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
		$lo_pdo = new \PDO(self::DB_CONNECTION_STRING);
		$lo_analyser = new Analyse();
		$lo_analyser->set_pdo_connection($lo_pdo);
		$lo_analyser->set_schema(file_get_contents('examples/example.json'));
		$lo_analyser->set_file('examples/example.csv');
		$lb_file_is_valid = $lo_analyser->analyse();

		$this->assertEquals(true, $lb_file_is_valid);
	}


	public function testGetEmptyErrorsReturnsEmptyArray()
	{
		$lo_analyser = new Analyse();
		$la_errors = $lo_analyser->get_errors();
		$this->assertEmpty($la_errors);
	}


	public function testAnalyseReturnsFalseOnMissingMandatoryColumnInCSVFile()
	{
		// Create a test CSV file with a missing mandatory "WEBSITE" column.
		$this->_createCSVFile(['FIRST_NAME', 'EMAIL_ADDRESS'], [['john', 'test@example.com']]);
		$lo_analyser = new Analyse();
		$lo_analyser->set_schema(file_get_contents('examples/example.json'));
		$lo_analyser->set_file('test.csv');
		$lb_file_is_valid = $lo_analyser->analyse();

		$this->assertFalse($lb_file_is_valid);
	}


	public function testErrorIsSetOnMissingMandatoryColumnInCSVFile()
	{
		// Create a test CSV file with a missing mandatory "WEBSITE" column.
		$this->_createCSVFile(['FIRST_NAME', 'EMAIL_ADDRESS'], [['john', 'test@example.com']]);
		$lo_analyser = new Analyse();
		$lo_analyser->set_schema(file_get_contents('examples/example.json'));
		$lo_analyser->set_file('test.csv');
		$lo_analyser->analyse();
		$la_errors = $lo_analyser->get_errors();

		$la_expected_error = ['<strong>1</strong> required column(s) missing:' => ['website']];
		$this->assertEquals($la_expected_error, $la_errors);

	}





}