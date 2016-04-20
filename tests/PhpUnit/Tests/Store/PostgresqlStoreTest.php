<?php
namespace PhpUnit\Tests\Store;

use \JsonTable\Analyse\Analyse;
use \tests\PhpUnit\Fixtures\Mock;
use \JsonTable\Store\PostgresqlStore;
use \tests\PhpUnit\Fixtures\Helper;

class PostgresqlStoreTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test that calling the store method with valid data results in the data being correctly stored.
     */
    public function testStoreStoresCorrectData()
    {
        $mock = new Mock();
        $pdo = $mock->PDO();

        $analyser = new Analyse();
        $analyser->setPdoConnection($pdo);
        $analyser->setSchema(Helper::getExampleSchemaString());
        $analyser->setFile(Helper::getExampleCSVLocation());
        $analyser::openFile();
        $analyser::setCsvHeaderColumns();

        $postgresqlStore = new PostgresqlStore();

        $fetchResults = [['id' => 1], ['id' => 2]];
        $mock->expectPdoExecute($pdo, $fetchResults);
        $expectedInsertedIds = [[['id' => 1], ['id' => 2]], [['id' => 1], ['id' => 2]]];
        $postgresqlStore->store('tableName', 'id');
        $actualInsertedIds = $postgresqlStore->getInsertedIds();

        $this->assertEquals($expectedInsertedIds, $actualInsertedIds);
    }


    /**
     * Test that if the store insert query fails an exception is thrown.
     */
    public function testFailedStoreQueryThrowsException()
    {
        $mock = new Mock();
        $pdo = $mock->PDO();

        $analyser = new Analyse();
        $analyser->setPdoConnection($pdo);
        $analyser->setSchema(Helper::getExampleSchemaString());
        $analyser->setFile(Helper::getExampleCSVLocation());
        $analyser::openFile();
        $analyser::setCsvHeaderColumns();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Could not insert row 1 into the database.');

        $postgresqlStore = new PostgresqlStore();

        $mock->expectPdoFailedExecute($pdo);
        $postgresqlStore->store('tableName', 'id');
    }
}
