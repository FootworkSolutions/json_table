<?php
namespace JsonTable\Validate\ForeignKey;

/**
 * Foreign key postgresql validator.
 *
 * @package JSON table
 */
class PostgresqlValidator implements \JsonTable\Validate\InterfaceForeignKeyValidator
{
    /**
     * Check that the foreign key hash matches the specified resource.
     *
     * @access public
     *
     * @param string $ps_row_hash           The hash of data from the CSV row to be validated.
     * @param string $ps_reference_resource The reference resource.
     * @param array  $pa_reference_fields   The reference fields.
     *
     * @return boolean Is the data valid.
     */
    public function validate($ps_row_hash, $ps_reference_resource, array $pa_reference_fields)
    {
        $ls_reference_fields = implode(" || ', ' || ", $pa_reference_fields);

        $ls_validation_sql =   "SELECT
                                    COUNT(*)
                                FROM
                                    $ps_reference_resource
                                WHERE
                                    $ls_reference_fields = :row_hash";

        // Prepare and execute the statement.
        $lo_statement = \JsonTable\Base::$_o_pdo_connection->prepare($ls_validation_sql);
        $lo_statement->bindParam(':row_hash', $ps_row_hash);
        $la_results = $lo_statement->execute();

        if (false === $la_results) {
            // The query failed.
            throw new \Exception("Could not validate the foreign key for $ps_reference_resource
                fields $ls_reference_fields with hash of $ps_row_hash.");
        }

        // Return whether any matching rows were found.
        return (0 !== $la_results[0]['count']);
    }
}
