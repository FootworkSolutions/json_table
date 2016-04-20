<?php
namespace PhpUnit\Tests\Validate\ForeignKey;
use \tests\PhpUnit\Fixtures\Mock;

use JsonTable\Validate\ForeignKey\PostgresqlValidator;

class PostgresqlValidatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test that an exception is thrown when the query to retrieve foreign keys results in an error.
     */
    public function testGetForeignKeyResultError()
    {
        $postgresqlValidator = new PostgresqlValidator();
        $base = $this->getMockForAbstractClass('\JsonTable\Base');
        $mock = new Mock();
        $pdo = $mock->PDO();
        $base->setPdoConnection($pdo);
        $mock->expectPdoFetchAllResult($pdo, false);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Could not validate the foreign key for tableName
                fields field1 || \', \' || field2 || \', \' || field3 with hash of exampleRowHash.');

        $postgresqlValidator->validate('exampleRowHash', 'tableName', ['field1', 'field2', 'field3']);
    }
}
