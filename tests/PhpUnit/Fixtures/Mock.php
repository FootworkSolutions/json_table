<?php
namespace tests\PhpUnit\Fixtures;

class Mock extends \PHPUnit_Framework_TestCase
{
    /**
     * Get a PDO mock object.
     *
     * @access public
     */
    public function PDO()
    {
        // Get a mock PDO object.
        return $this->getMockBuilder('\PDO')
                    ->disableOriginalConstructor()
                    ->getMock();
    }
}
