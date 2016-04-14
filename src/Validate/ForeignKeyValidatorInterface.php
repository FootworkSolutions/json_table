<?php
namespace JsonTable\Validate;

/**
 * Foreign key validator interface.
 *
 * @package JSON table
 */
interface ForeignKeyValidatorInterface
{
    /**
     * Check that the foreign key hash matches the specified resource.
     *
     * @param string $rowHash The hash of data from the CSV row to be validated.
     * @param string $referenceResource The reference resource.
     * @param array $referenceFields The reference fields.
     *
     * @return boolean Is the data valid.
     */
    public function validate($rowHash, $referenceResource, array $referenceFields);
}
