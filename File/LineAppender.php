<?php

namespace Contrib\CommonBundle\File;

/**
 * File line appender.
 */
class LineAppender extends AbstractFileWriterHandler
{
    /**
     * Append line to file.
     *
     * @param string $line Line to append.
     * @param integer $length Appending length.
     * @return integer Number of bytes written to the file.
     */
    public function append($line, $length = null)
    {
        if ($length === null || !is_int($length)) {
            return fwrite($this->handle, $this->newLine($line));
        }

        return fwrite($this->handle, $this->newLine($line), $length);
    }
}
