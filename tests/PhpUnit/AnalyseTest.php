<?php
namespace tests\PhpUnit;

use \JsonTable\Analyse;

class AnalyseTest extends \PHPUnit_Framework_TestCase
{
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
		$lo_pdo = new \PDO('pgsql:host=localhost;port=5432;dbname=test1;user=json_test;password=mypass');
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
		$lo_pdo = new \PDO('pgsql:host=localhost;port=5432;dbname=test1;user=json_test;password=mypass');
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
}