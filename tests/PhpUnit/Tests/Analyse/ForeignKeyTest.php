<?php
namespace tests\PhpUnit\Tests;

use \JsonTable\Analyse\Analyse;
use \tests\PhpUnit\Fixtures\Mock;
use \JsonTable\Analyse\ForeignKey;
use \tests\PhpUnit\Fixtures\Helper;

class ForeignKeyTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test that if there are no foreign keys defined in the schema the foreign key validation should return true.
     */
    public function testNoForeignKeysReturnsTrue()
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
                "type": "string"
            }]}'
        );
        
        $foreignKey = new ForeignKey();
        $this->assertTrue($foreignKey->validate());
    }


    /**
     * Test that trying to use an invalid data package type results in an exception being thrown.
     * 
     * @dataProvider    providerInvalidDataProviderValues
     */
    public function testInvalidDataPackageThrowsException($invalidData)
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
                "type": "string"
            }],
            "foreignKeys": [{
                "fields": ["FIRST_NAME"],
                "reference": {
			        "datapackage": "' . $invalidData . '"
			    }
            }]}'
        );
        $analyser->setFile(Helper::getExampleCSVLocation());
        $analyser::openFile();
        $foreignKey = new ForeignKey();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Only postgresql foreign keys are currently supported.
                Please ensure that the datapackage attribute on all foreign keys is defined
                as &quot;database&quot; or is omitted.');

        $foreignKey->validate();
    }


    /**
     * Provider of invalid date values.
     *
     * @return  array   The invalid date values.
     */
    public function providerInvalidDataProviderValues()
    {
        return [
            [''],
            [' '],
            ['not_a_valid_data_package'],
            ['0000000'],
            [null],
            [true],
            [false]
        ];
    }



}