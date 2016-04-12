<?php
namespace JsonTable\Validate\ForeignKey;

use \JsonTable\Validate\ForeignKeyValidatorInterface;
use \JsonTable\Base;

/**
 * Foreign key postgresql validator.
 *
 * @package JSON table
 */
class PostgresqlValidator implements ForeignKeyValidatorInterface
{
    /**
     * Check that the foreign key hash matches the specified resource.
     *
     * @access  public
     *
     * @param   string  $rowHash           The hash of data from the CSV row to be validated.
     * @param   string  $referenceResource The reference resource.
     * @param   array   $referenceFields   The reference fields.
     *
     * @return  boolean Is the data valid.
     *
     * @throws  \Exception if the foreign key couldn't be validated.
     */
    public function validate($rowHash, $referenceResource, array $referenceFields)
    {
        $referenceFields = implode(" || ', ' || ", $referenceFields);

        $validationSql =   "SELECT
                                    COUNT(*)
                                FROM
                                    $referenceResource
                                WHERE
                                    $referenceFields = :row_hash";

        $statement = Base::$pdoConnection->prepare($validationSql);
        $statement->bindParam(':row_hash', $rowHash);
        $results = $statement->execute();

        if (false === $results) {
            throw new \Exception("Could not validate the foreign key for $referenceResource
                fields $referenceFields with hash of $rowHash.");
        }

        return (0 !== $results[0]['count']);
    }
}
