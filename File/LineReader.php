<?php

namespace Contrib\CommonBundle\File;

/**
 * File line reader.
 */
class LineReader extends AbstractFileHandler
{
    /**
     * Return file line (fgets() function wrapper).
     *
     * @param integer $length Length to read.
     * @return string File contents.
     */
    public function read($length = null)
    {
        if ($length === null || !is_int($length)) {
            return fgets($this->handle);
        }

        return fgets($this->handle, $length);
    }
}
