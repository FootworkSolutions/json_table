<?php
namespace JsonTable\Analyse;

/**
 * Statistics information regarding the analysis.
 *
 * @package    JSON table
 */
class Statistics
{
    /**
     * @var array   Statistics relating to the file analysis.
     */
    protected static $statistics = [
        'rows_with_errors' => [],
        'percent_rows_with_errors' => 0,
        'rows_analysed' => 0
    ];


    /**
     * Get the statistics about the file analysis.
     *
     * @return  array   The statistics.
     */
    public function getStatistics()
    {
        $this->cleanErrorRow();

        if (self::$statistics['rows_analysed'] > 0) {
            self::$statistics['percent_rows_with_errors'] = $this->getErrorRowPercent();
        }

        return self::$statistics;
    }
    
    
    /**
     * Add the row number of a row with an error to the analysis statistics.
     *
     * @param   int $rowNumber   The position of the row with the error in the CSV file.
     *
     * @return  void
     */
    public function setErrorRow($rowNumber)
    {
        self::$statistics['rows_with_errors'][] = $rowNumber;
    }


    /**
     * Set the number of rows that have been analysed.
     *
     * @param   int $rowsAnalysedCount   The number of rows analysed.
     *
     * @return  void
     */
    public function setRowsAnalysed($rowsAnalysedCount)
    {
        self::$statistics['rows_analysed'] = $rowsAnalysedCount;
    }


    /**
     * Clean the rows with errors statistic.
     * This removes duplicated records where the same row has had multiple errors.
     *
     * @return  void
     */
    private function cleanErrorRow()
    {
        self::$statistics['rows_with_errors'] = array_unique(self::$statistics['rows_with_errors']);
    }


    /**
     * Get the percentage of analysed rows that have had a error with them.
     *
     * @return  int The percentage.
     */
    private function getErrorRowPercent()
    {
        return round((count(self::$statistics['rows_with_errors']) / self::$statistics['rows_analysed']) * 100);
    }
}