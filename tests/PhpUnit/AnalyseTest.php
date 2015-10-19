<?php
namespace tests\PhpUnit;

use \JsonTable\Analyse;

class AnalyseTest extends \PHPUnit_Framework_TestCase
{
	public function testSchemaThrowsExceptionWithInvalidSchemaString()
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
}