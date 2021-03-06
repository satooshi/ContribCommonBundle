<?php

namespace Contrib\Bundle\CommonBundle\File;

use Contrib\Bundle\CommonBundle\Util\String\Utf8;

/**
 * File client.
 *
 * usage:
 *
 * try {
 *    $file = new FileClient('/path/to/read');
 *    $lines = $file->read();
 * } catch (\RuntimeException $e) {
 *    // on failure
 * }
 */
class FileClient
{
    /**
     * File path.
     *
     * @var string
     */
    protected $path;

    /**
     * New line to be written (Default is PHP_EOL).
     *
     * @var string
     */
    protected $newLine;

    /**
     * Whether to throw exception.
     *
     * @var boolean
     */
    protected $throwException;

    /**
     * Whether to use auto_detect_line_endings.
     * @var boolean
     */
    protected $autoDetectLineEnding;

    /**
     * Whether the client suspended to read file.
     *
     * @var boolean
     */
    protected $isSuspended = false;

    /**
     * LineReader.
     *
     * @var LineReader
     */
    protected $lineReader;

    /**
     * LineWriter.
     *
     * @var LineWriter
     */
    protected $lineWriter;

    /**
     * LineAppender.
     *
     * @var LineAppender
     */
    protected $lineAppender;

    /**
     * Constructor.
     *
     * @param string  $path                 File path.
     * @param string  $newLine              New line to be written (Default is PHP_EOL).
     * @param boolean $throwException       Whether to throw exception.
     * @param boolean $autoDetectLineEnding Whether to use auto_detect_line_endings.
     */
    public function __construct($path, $newLine = PHP_EOL, $throwException = true, $autoDetectLineEnding = true)
    {
        $this->path                 = $path;
        $this->newLine              = $newLine;
        $this->throwException       = $throwException;
        $this->autoDetectLineEnding = $autoDetectLineEnding;

        ini_set('auto_detect_line_endings', $this->autoDetectLineEnding);
    }

    // API

    /**
     * Return whether the file is readable.
     *
     * @return boolean true if the file is readable.
     * @throws \RuntimeException Throws if the file is not readable and $throwException is set to true.
     */
    public function isReadable()
    {
        return FileValidator::canRead($this->path, $this->throwException);
    }

    /**
     * Return whether the file is writable.
     *
     * @return boolean true if the file is writable.
     * @throws \RuntimeException Throws if the file is not writable and $throwException is set to true.
     */
    public function isWritable()
    {
        return FileValidator::canWrite($this->path, $this->throwException);
    }

    /**
     * Return file contents (file_get_contents() function wrapper).
     *
     * @return string File contents
     * @throws \RuntimeException Throws on failure if $throwException is set to true.
     */
    public function read()
    {
        if ($this->isReadable()) {
            $data = file_get_contents($this->path);

            if (false !== $data) {
                return $data;
            }

            if ($this->throwException) {
                throw new \RuntimeException("Failed to read file : $this->path.");
            }
        }

        return false;
    }

    /**
     * Return file line (fgets() function wrapper).
     *
     * @param integer $length Length to read.
     * @return string File contents.
     * @throws \RuntimeException Throws on failure if $throwException is set to true.
     */
    public function readLine($length = null)
    {
        if (!isset($this->lineReader)) {
            $handle = $this->openForRead($this->path);

            if ($handle === false) {
                return false;
            }

            $this->lineReader = new LineReader($handle);
        }

        return $this->lineReader->read($length);
    }

    /**
     * Write lines to file (fule_put_contents() function wrapper).
     *
     * @param string $lines Lines to write.
     * @return integer Number of bytes written to the file.
     * @throws \RuntimeException Throws on failure if $throwException is set to true.
     */
    public function write($lines)
    {
        if ($this->isWritable()) {
            $bytes = file_put_contents($this->path, $lines);

            if (false !== $bytes) {
                return $bytes;
            }

            if ($this->throwException) {
                throw new \RuntimeException("Failed to write lines to file : $this->path.");
            }
        }

        return false;
    }

    /**
     * Write line to file (fwrite() function wrapper).
     *
     * @param string $line Line to write.
     * @param integer $length Length to write.
     * @return integer Number of bytes written to the file.
     * @throws \RuntimeException Throws on failure if $throwException is set to true.
     */
    public function writeLine($line, $length = null)
    {
        if (!isset($this->lineWriter)) {
            $handle = $this->openForWrite($this->path);

            if ($handle === false) {
                return false;
            }

            $this->lineWriter = new LineWriter($handle, $this->newLine);
        }

        return $this->lineWriter->write($line, $length);
    }

    /**
     * Append lines to file (file_put_contents() function wrapper).
     *
     * @param string $lines Lines to append.
     * @return integer Number of bytes written to the file.
     * @throws \RuntimeException Throws on failure if $throwException is set to true.
     */
    public function append($lines)
    {
        if ($this->isWritable()) {
            $bytes = file_put_contents($this->path, $lines, FILE_APPEND | LOCK_EX);

            if (false !== $bytes) {
                return $bytes;
            }

            if ($this->throwException) {
                throw new \RuntimeException("Failed to append lines to file : $this->path.");
            }
        }

        return false;
    }

    /**
     * Append line to file (fwrite() function wrapper).
     *
     * @param string $line Line to append.
     * @param integer $length Appending length.
     * @return integer Number of bytes written to the file.
     * @throws \RuntimeException Throws on failure if $throwException is set to true.
     */
    public function appendLine($line, $length = null)
    {
        if (!isset($this->lineAppender)) {
            $handle = $this->openForWrite($this->path);

            if ($handle === false) {
                return false;
            }

            $this->lineAppender = new LineAppender($handle, $this->newLine);
        }

        return $this->lineAppender->append($line, $length);
    }

    /**
     * Apply a callback to every line except for empty line.
     *
     * @param callable $callback  function ($line, $numLine).
     * @param boolean  $skipEmpty Whether to skip count if empty line.
     * @param integer  $limit     Count of the limit.
     * @param integer  $offset    Offset of the limit.
     * @return \Iterator|false Iterator on success, false on failure.
     */
    public function walk($callback, $skipEmptyCount = true, $limit = -1, $offset = 0)
    {
        $limit  = $this->filterLimit($limit);
        $offset = $this->filterOffset($offset);

        $iterator = $this->createLineIterator($this->path, $skipEmptyCount, $limit, $offset);

        if ($iterator === false) {
            return false;
        }

        if ($skipEmptyCount && $limit > 0) {
            $this->iterateLimit($callback, $iterator, $limit);
        } else {
            $this->iterate($callback, $iterator);
        }

        if ($iterator instanceof \IteratorIterator) {
            $this->isSuspended = $iterator->getInnerIterator()->valid();
        } elseif ($iterator instanceof \Iterator) {
            $this->isSuspended = $iterator->valid();
        }

        return $iterator;
    }

    // internal method

    /**
     * Open file for read.
     *
     * @param string $path File path.
     * @return resource|boolean File handle on success, false on faiiure
     * @throws \RuntimeException Throws on failure if $throwException is set to true.
     */
    protected function openForRead($path)
    {
        if ($this->isReadable()) {
            $handle = fopen($path, 'r');

            if (false === $handle && $this->throwException) {
                throw new \RuntimeException("Failed to read file for read : $path.");
            }

            return $handle;
        }

        return false;
    }

    /**
     * Open file for write.
     *
     * @param string $path File path.
     * @return resource|boolean File handle on success, false on faiiure
     * @throws \RuntimeException Throws on failure if $throwException is set to true.
     */
    protected function openForWrite($path)
    {
        if ($this->isWritable()) {
            $handle = fopen($path, 'w');

            if (false === $handle && $this->throwException) {
                throw new \RuntimeException("Failed to open file for write : $path.");
            }

            return $handle;
        }

        return false;
    }

    /**
     * Open file for append
     *
     * @param string $path File path.
     * @return resource|boolean File handle on success, false on faiiure.
     * @throws \RuntimeException Throws on failure if $throwException is set to true.
     */
    protected function openForAppend($path)
    {
        if ($this->isWritable()) {
            $handle = fopen($path, 'a');

            if (false === $handle && $this->throwException) {
                throw new \RuntimeException("Failed to open file for append : $path.");
            }

            return $handle;
        }

        return false;
    }



    /**
     * Iterate iterator.
     *
     * @param callable $callback
     * @param \Iterator $iterator
     * @return void
     */
    protected function iterate($callback, \Iterator $iterator)
    {
        foreach ($iterator as $numLine => $data) {
            $line = $this->trimLine($data);

            if (empty($line)) {
                // skip empty line
                continue;
            }

            $items = $this->filterIteratedLine($line);

            if (!$callback($items, $numLine)) {
                return;
            }
        }
    }

    /**
     * Iterate iterator until limit excluding empty line count.
     *
     * @param callable  $callback
     * @param \Iterator $iterator
     * @param integer   $limit
     * @return void
     */
    protected function iterateLimit($callback, \Iterator $iterator, $limit)
    {
        $readLine = 0;

        foreach ($iterator as $numLine => $data) {
            $line = $this->trimLine($data);

            if (empty($line)) {
                // skip empty line
                continue;
            }

            $items = $this->filterIteratedLine($line);

            if (!$callback($items, $numLine)) {
                return;
            }

            $readLine++;

            if ($readLine === $limit) {
                return;
            }
        }
    }

    /**
     * Trim line.
     *
     * @param string $line
     * @return string
     */
    protected function trimLine($line)
    {
        return trim(Utf8::auto($line));
    }

    /**
     * Filter iterated line in walk().
     *
     * @param string $line
     * @return mixed
     */
    protected function filterIteratedLine($line)
    {
        return $line;
    }

    /**
     * Create LineIterator.
     *
     * @param string  $path      File path.
     * @param boolean $skipEmpty Whether to skip count if empty line.
     * @param integer $limit     Count of the limit.
     * @param integer $offset    Offset of the limit.
     * @return \Iterator LimitIterator if limit specified, LineIterator otherwise.
     */
    protected function createLineIterator($path, $skipEmptyCount, $limit = -1, $offset = 0)
    {
        $handle = $this->openForRead($path);

        if ($handle === false) {
            return false;
        }

        $iterator = new LineIterator($handle);

        if ($limit <= 0 || $skipEmptyCount) {
            return $iterator;
        }

        return new \LimitIterator($iterator, $offset, $limit);
    }

    /**
     * Filter optional limit.
     *
     * @param integer $limit Count of the limit.
     * @return integer
     */
    protected function filterLimit($limit)
    {
        if (is_numeric($limit)) {
            $limit = (int)$limit;
        }

        if (!is_int($limit)) {
            $limit = -1;
        }

        return $limit;
    }

    /**
     * Filter optional offset.
     *
     * @param integer $offset Offset of the limit.
     * @return integer
     */
    protected function filterOffset($offset)
    {
        if (is_numeric($offset)) {
            $offset = (int)$offset;
        }

        if (!is_int($offset)) {
            $offset = 0;
        }

        return $offset;
    }

    // accessor

    /**
     * Return file path.
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Return whether the client suspended to read file.
     *
     * @return boolean
     */
    public function isSuspended()
    {
        return $this->isSuspended;
    }
}
