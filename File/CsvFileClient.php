<?php

namespace Contrib\CommonBundle\File;

/**
 * CSV file client.
 */
class CsvFileClient extends FileClient
{
    // API

    /**
     * Read all CSV lines (fgetcsv() function wrapper).
     *
     * @return array CSV lines.
     */
    public function readAllCsv()
    {
        $handle = $this->openForRead();

        if ($handle === false) {
            return false;
        }

        $reader = new CsvLineReader($handle);

        return $reader->readAllCsv();
    }

    /**
     * Read CSV fields of all lines.
     *
     * @param string $length Length to read.
     * @return array|false CSV fields on success, false on failure.
     */
    public function readAll($length = null)
    {
        $handle = $this->openForRead();

        if ($handle === false) {
            return false;
        }

        $reader = new CsvLineReader($handle);

        return $reader->readAll($length);
    }

    // internal method

    /**
     * {@inheritdoc}
     *
     * @see \Contrib\CommonBundle\File\FileClient::filterIteratedLine()
     */
    protected function filterIteratedLine($line)
    {
        return CsvLineReader::parseCsvLine($line);
    }
}
