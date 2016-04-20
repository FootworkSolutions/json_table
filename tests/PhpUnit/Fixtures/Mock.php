<?php
namespace tests\PhpUnit\Fixtures;

class Mock extends \PHPUnit_Framework_TestCase
{
    /**
     * Get a PDO mock object.
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
     * @return  object  The mock PDOStatement object.
     */
    public function PDOStatement()
    {
        return $this->getMockBuilder('\PDOStatement')
            ->disableOriginalConstructor()
            ->getMock();
    }


    /**
     * This defines the expected result from a group of PDO prepare, bindParam and fetchAll statements.
     *
     * @param   object   $pdoMock           The mock PDO object to use.
     * @param   mixed    $expectedDbResult  The expected result from the database.
     *
     * @return  void
     */
    public function expectPdoFetchAllResult($pdoMock, $expectedResult)
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


    /**
     * This defines the expected result from a group of PDO prepare, bindParam and execute statements.
     *
     * @param   object   $pdoMock           The mock PDO object to use.
     * @param   mixed    $expectedDbResult  The expected result from the database.
     *
     * @return  void
     */
    public function expectPdoExecute($pdoMock, $expectedResult = null)
    {
        $pdoStatement = $this->PDOStatement();

        $pdoMock->expects($this->any())
            ->method('prepare')
            ->will($this->returnValue($pdoStatement));

        $pdoStatement->expects($this->any())
            ->method('bindParam')
            ->will($this->returnValue(true));

        $pdoStatement->expects($this->any())
            ->method('execute')
            ->will($this->returnValue(true));

        if (!is_null($expectedResult)) {
            $pdoStatement->expects($this->any())
                ->method('fetch')
                ->will($this->returnValue($expectedResult));
        }
            
    }


    /**
     * This defines the expected result a failed PDO execute call.
     *
     * @param   object   $pdoMock           The mock PDO object to use.
     *
     * @return  void
     */
    public function expectPdoFailedExecute($pdoMock)
    {
        $pdoStatement = $this->PDOStatement();

        $pdoMock->expects($this->any())
            ->method('prepare')
            ->will($this->returnValue($pdoStatement));

        $pdoStatement->expects($this->any())
            ->method('bindParam')
            ->will($this->returnValue(true));

        $pdoStatement->expects($this->any())
            ->method('execute')
            ->will($this->returnValue(false));
    }


    /**
     * Get a Statistics mock object.
     *
     * @return  object  The mock Statistics object.
     */
    public function statistics()
    {
        return $this->getMockBuilder('\JsonTable\Analyse\Statistics')
            ->getMock();
    }


    /**
     * Get a Error mock object.
     *
     * @return  object  The mock Statistics object.
     */
    public function error()
    {
        return $this->getMockBuilder('\JsonTable\Analyse\Error')
            ->getMock();
    }
}
