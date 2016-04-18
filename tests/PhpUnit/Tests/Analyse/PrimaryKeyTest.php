<?php
namespace tests\PhpUnit\Tests;

use \JsonTable\Analyse\Analyse;
use \tests\PhpUnit\Fixtures\Mock;
use \JsonTable\Analyse\PrimaryKey;
use \tests\PhpUnit\Fixtures\Helper;

class PrimaryKeyTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Remove any test files that have been created during the testing process.
     */
    protected function tearDown()
    {
        Helper::deleteTestCSVFile();
    }


    /**
     * Test that the validate function returns true if no primary key is defined.
     */
    public function testNoPrimaryKeyReturnsTrue()
    {
        $analyser = new Analyse();
        $analyser->setSchema('{
            "fields": [{
                "name": "FIRST_NAME",
                "title": "First Name",
                "description": "The user\'s first name",
                "type": "string"
            }]}'
        );
        $primaryKey = new PrimaryKey();
        $this->assertTrue($primaryKey->validate());
    }


    /**
     * Test that the correct error is set if a duplicate primary key is found in the CSV file.
     */
    public function testErrorOnDuplicatePrimaryKey()
    {
        $analyser = new Analyse();
        $analyser->setSchema('{
            "fields": [{
                "name": "FIRST_NAME",
                "title": "First Name",
                "description": "The user\'s first name",
                "type": "string"
            }],
            "primaryKey": ["FIRST_NAME"]}'
        );

        Helper::createTestCSVFile(['FIRST_NAME', 'EMAIL_ADDRESS', 'WEBSITE'], [
            ['duplicate_primary_key', 'test@example.com', 'www.example2.com'],
            ['duplicate_primary_key', 'something@example.com', 'www.example.com']
        ]);

        $analyser->setFile(Helper::getTestCSVFile());
        $analyser::openFile();
        $analyser::setCsvHeaderColumns();

        $primaryKey = new PrimaryKey();
        $primaryKey->validate();

        $errors = $analyser->getErrors();
        $expectedErrors = [
            'There are <strong>1</strong> rows that have duplicated primary keys:' => [
                'The data in columns &quot;FIRST_NAME&quot; should be unique,
                but rows 1 &amp; 2 have the same values of &quot;duplicate_primary_key&quot;']
        ];
        
        $this->assertEquals($expectedErrors, $errors);
    }
}
