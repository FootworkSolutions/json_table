<?php
namespace PhpUnit\Tests;

use \tests\PhpUnit\Fixtures\Mock;
use \JsonTable\Store;


class StoreTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test that the abstract store class file is loaded when the load method is called.
     */
    public function testLoadIncludesAbstractStore()
    {
        new Store;
        Store::load('postgresql');
        $this->assertTrue(class_exists('\JsonTable\Store\AbstractStore'));
    }


    /**
     * Test that a valid store class is loaded when the load method is called.
     */
    public function testLoadValidStoreFile()
    {
        new Store;
        Store::load('postgresql');
        $this->assertTrue(class_exists('\JsonTable\Store\PostgresqlStore'));
    }


    /**
     * Test that an exception is thrown when an invalid store class is requested to be loaded.
     *
     * @dataProvider    providerInvalidStoreTypes
     */
    public function testLoadInvalidStoreFileThrowsException($invalidStoreType)
    {
        new Store;
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Could not load the store file for " . ucwords($invalidStoreType));
        Store::load($invalidStoreType);
    }


    /**
     * Provider of invalid store types.
     */
    public function providerInvalidStoreTypes()
    {
        return [
            [null],
            [1],
            [true],
            [''],
            [' '],
            ['not_valid']
        ];
    }


    /**
     * Test that a valid store object is returned from the load function.
     */
    public function testStoreReturnedFromLoad()
    {
        new Store;
        $store = Store::load('postgresql');
        $this->assertTrue($store instanceof \JsonTable\Store\PostgresqlStore);
    }
}