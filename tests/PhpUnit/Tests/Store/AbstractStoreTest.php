<?php
namespace PhpUnit\Tests\Store;

use \JsonTable\Store\AbstractStore;


class AbstractStoreTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test that the isoDateFromFormat static function returns a valid iso date from a valid date input.
     *
     * @dataProvider    providerValidDatesAndFormats
     */
    public function testIsoDateFromFormatReturnsValidDate($format, $dateToConvert, $expectedIsoDate)
    {
        $abstractStore = $this->getMockForAbstractClass('\JsonTable\Store\AbstractStore');
        $actualIsoDate = $abstractStore::isoDateFromFormat($format, $dateToConvert);
        $this->assertEquals($expectedIsoDate, $actualIsoDate);
    }


    /**
     * Provider of valid dates and formats.
     */
    public function providerValidDatesAndFormats()
    {
        return [
            ['j-M-Y', '15-Feb-2009', '2009-02-15'],
            ['Y-m-d', '2012-10-17', '2012-10-17'],
            ['dmY', '01011972', '1972-01-01'],
            ['m/d/y h:i', '02/26/11 08:00', '2011-02-26']
        ];
    }

    /**
     * Test that the isoDateFromFormat static function returns a valid iso date from a valid date input.
     *
     * @dataProvider    providerInvalidDatesAndFormats
     */
    public function testIsoDateFromInvalidFormatThrowsException($format, $dateToConvert)
    {
        $abstractStore = $this->getMockForAbstractClass('\JsonTable\Store\AbstractStore');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Could not reformat date $dateToConvert from format $format");

        $abstractStore::isoDateFromFormat($format, $dateToConvert);
    }


    /**
     * Provider of valid dates and formats.
     */
    public function providerInvalidDatesAndFormats()
    {
        return [
            ['jasaM-Y', '15sdd-Feb-2009'],
            ['Y-m-d', '20121017'],
            ['', '01011972'],
            ['m/d/y h:i', ''],
            [true, '2012-04-02']
        ];
    }


    /**
     * Test that valid boolean type values are converted into actual booleans.
     *
     * @dataProvider providerValidBooleanValues
     */
    public function testValidBooleanConversions($validBooleanType, $expectedBoolean)
    {
        $abstractStore = $this->getMockForAbstractClass('\JsonTable\Store\AbstractStore');
        $actualBoolean = $abstractStore::booleanFromFilterBooleans($validBooleanType);

        $this->assertEquals($expectedBoolean, $actualBoolean);
    }


    /**
     * Provider of valid boolean type values and their expected actual boolean values.
     */
    public function providerValidBooleanValues()
    {
        return [
            ['Yes', true],
            ['YES', true],
            ['On', true],
            ['ON', true],
            ['true', true],
            ['TRUE', true],
            [1, true],
            [true, true],
            ['No', false],
            ['NO', false],
            ['off', false],
            ['OFF', false],
            ['false', false],
            ['FALSE', false],
            [0, false],
            [false, false],
            ['', null],
            [' ', null],
            ['anything', null],
            [null, null],
            [2, null]
        ];
    }
}
