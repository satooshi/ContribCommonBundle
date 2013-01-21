<?php

namespace Contrib\Bundle\CommonBundle\File;

/**
 * File line writer.
 */
class LineWriter extends AbstractFileWriterHandler
{
    /**
     * Write line to file (fwrite() function wrapper).
     *
     * @param string $line Line to write.
     * @param integer $length Length to write.
     * @return integer Number of bytes written to the file.
     */
    public function write($line, $length = null)
    {
        if ($length === null || !is_int($length)) {
            return fwrite($this->handle, $this->newLine($line));
        }

        return fwrite($this->handle, $this->newLine($line), $length);
    }
}
