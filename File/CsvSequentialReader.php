<?php

namespace Contrib\CommonBundle\File;

use Doctrine\ORM\EntityManager;
use Contrib\CommonBundle\File\Exception\CsvException;
use Contrib\CommonBundle\File\Exception\FatalCsvException;
use Contrib\CommonBundle\File\Exception\InvalidCsvException;

abstract class CsvSequentialReader
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected $em;

    protected $headerCount;

    // data

    /**
     * CSV errors.
     *
     * @var array
     */
    protected $errors = array();

    /**
     * Number of header items.
     *
     * @var int
     */
    protected $maxlength = 0;

    /**
     * Minium number of header items.
     *
     * @var int
     */
    protected $requireItemCount = 0;

    /**
     * Constructor.
     *
     * @param EntityManager $em
     * @param integer       $headerCount
     */
    public function __construct(EntityManager $em, $headerCount = 0)
    {
        $this->em = $em;
        $this->headerCount = $headerCount;
    }

    // API

    public function readline($items, $numLine)
    {
        $numLine++; // $numLine is 0-based index
        $length = count($items);

        try {
            if ($numLine <= $this->headerCount) {
                return $this->readHeader($items, $numLine, $length);
            }

            return $this->readBody($items, $numLine, $length);
        } catch (CsvException $e) {
            $this->errors[$numLine][] = $e->getMessage();

            return false;
        }
    }

    public function strip($value)
    {
        return mb_convert_kana(trim($value), 'sKV');
    }

    // internal method

    protected function readHeader($items, $numLine, $length)
    {
        try {
            $this->assertHeader($items, $numLine, $length);
        } catch (InvalidCsvException $e) {
            $this->errors[$numLine][] = $e->getMessage();
        }

        $this->maxlength = $length;

        return $this->processHeader($items, $numLine, $length);
    }

    protected function readBody($items, $numLine, $length)
    {
        try {
            $this->assertBody($items, $numLine, $length);
        } catch (InvalidCsvException $e) {
            $this->errors[$numLine][] = $e->getMessage();
        }

        return $this->processBody($items, $numLine, $length);
    }

    abstract protected function assertHeader($items, $numLine, $length);
    abstract protected function processHeader($items, $numLine, $length);

    abstract protected function assertBody($items, $numLine, $length);
    abstract protected function processBody($items, $numLine, $length);

    // accessor

    /**
     * Return CSV errors.
     *
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }
}
