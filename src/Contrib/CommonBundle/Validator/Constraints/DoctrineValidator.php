<?php

namespace Contrib\CommonBundle\Validator\Constraints;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class DoctrineValidator extends ConstraintValidator
{
    /**
     * @var Doctrine\Bundle\DoctrineBundle\Registry
     */
    protected $doctrine;

    /**
     * Constructor.
     *
     * @param Doctrine\Bundle\DoctrineBundle\Registry $doctrine
     */
    public function __construct(Registry $doctrine)
    {
        $this->doctrine = $doctrine;
    }
}
