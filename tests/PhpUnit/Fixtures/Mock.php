<?php
namespace tests\PhpUnit\Fixtures;

class Mock extends \PHPUnit_Framework_TestCase
{
    /**
     * Get a PDO mock object.
     *
     * @access  public
     *
     * @return  object  The mock PDOStatement object.
     */
    public function PDO()
    {
        return $this->getMockBuilder('\PDO')
                    ->disableOriginalConstructor()
                    ->getMock();
    }


    /**
     * Get a PDOStatement mock object.
     *
     * @access public
     *
     * @return  object  The mock PDOStatement object.
     */
    public function PDOStatement()
    {
        return $this->getMockBuilder('\PDOStatement')
            ->disableOriginalConstructor()
            ->getMock();
    }


    /**
     * This defines the expected result from a group of PDO prepare, bindParam and execute statements.
     *
     * @access public
     *
     * @param   mixed    $expectedDbResult  The expected result from the database.
     *
     * @return  void
     */
    public function expectFetchAllResult($pdoMock, $expectedResult)
    {
        $pdoStatement = $this->PDOStatement();

        $pdoMock->expects($this->any())
            ->method('prepare')
            ->will($this->returnValue($pdoStatement));

        $pdoStatement->expects($this->any())
            ->method('bindParam')
            ->will($this->returnValue(true));

        $pdoStatement->expects($this->any())
            ->method('fetchAll')
            ->will($this->returnValue($expectedResult));
    }
}
