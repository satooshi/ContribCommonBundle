<?php

namespace Contrib\CommonBundle\File;

/**
 * CSV file line reader.
 */
class CsvLineReader extends LineReader
{
    // static

    /**
     * Parse CSV line to fields.
     *
     * @param string $line
     * @return array CSV fields.
     */
    public static function parseCsvLine($line)
    {
        $csvFields = explode(',', $line);
        $fields    = array();

        foreach ($csvFields as $csvField) {
            $fields[] = trim($csvField, '"');
        }

        return $fields;
    }

    // API

    /**
     * Read CSV lines (fgetcsv() function wrapper).
     *
     * @return array CSV items.
     */
    public function readCsv()
    {
        return fgetcsv($this->handle);
    }

    /**
     * Read all CSV lines (fgetcsv() function wrapper).
     *
     * @return CSV lines.
     */
    public function readAllCsv()
    {
        $content = array();

        while (false !== $fields = $this->readCsv()) {
            $content[] = $fields;
        }

        return $content;
    }

    /**
     * Read CSV fields.
     *
     * @param string $length Length to read.
     * @return array|false CSV fields on success, false on failure.
     */
    public function readFields($length = null)
    {
        $line = $this->read($length);

        if ($line === false) {
            return false;
        }

        return static::parseCsvLine($line);
    }

    /**
     * Read CSV fields of all lines.
     *
     * @param string $length Length to read.
     * @return array CSV fields.
     */
    public function readAll($length = null)
    {
        $content = array();

        while (false !== $fields = $this->readFields($length)) {
            $content[] = $fields;
        }

        return $content;
    }
}
