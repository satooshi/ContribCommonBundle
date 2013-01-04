<?php

namespace Contrib\CommonBundle\File;

/**
 * Iterator for file read.
 */
class LineIterator extends AbstractFileHandler implements \Iterator
{
    /**
     * Current line number.
     *
     * @var integer
     */
    protected $numLine;

    /**
     * Current read line.
     *
     * @var string
     */
    protected $line;

    // Iterator interface

    /**
     * {@inheritdoc}
     *
     * @see Iterator::rewind()
     */
    public function rewind()
    {
        fseek($this->handle, 0);

        $this->line    = fgets($this->handle);
        $this->numLine = 0;
    }

    /**
     * {@inheritdoc}
     *
     * @see Iterator::valid()
     */
    public function valid()
    {
        return $this->line !== false;
    }

    /**
     * {@inheritdoc}
     *
     * @see Iterator::current()
     */
    public function current()
    {
        return $this->line;
    }

    /**
     * {@inheritdoc}
     *
     * @see Iterator::key()
     */
    public function key()
    {
        return $this->numLine;
    }

    /**
     * {@inheritdoc}
     *
     * @see Iterator::next()
     */
    public function next()
    {
        if ($this->valid()) {
            $this->line = fgets($this->handle);

            $this->numLine++;
        }
    }
}
