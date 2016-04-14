<?php
namespace JsonTable\Analyse;

/**
 * Foreign key validator interface.
 *
 * @package JSON table
 */
interface AnalyseInterface
{
    /**
     * Validate that the file is valid.
     *
     * @return  boolean Whether the file is valid.
     */
    public function validate();
}
