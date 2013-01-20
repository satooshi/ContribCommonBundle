<?php

namespace Contrib\CommonBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\DBAL\Connection;

abstract class AbstractCommand extends ContainerAwareCommand
{
    /**
     * Default connection name.
     *
     * @var string
     */
    protected $defaultConnection = null;

    /**
     * @var OutputInterface
     */
    protected $output;

    // internal api

    /**
     * {@inheritdoc}
     *
     * @see \Symfony\Component\Console\Command\Command::execute()
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;

        $this->startStamp();
        $this->console('started.');

        try {
            $returnValue = $this->doWork($input);

            $this->console('success.');
            $this->console($this->stamp());

            return (int)$returnValue;
        } catch (\Exception $e) {
            $this->console('exception occurred.');
            $this->console($this->stamp());
            $this->console($e->getTraceAsString());

            throw $e;
        }
    }

    /**
     * command worker.
     *
     * @param InputInterface $input
     */
    abstract protected function doWork(InputInterface $input);

    // shortcut

    /**
     * @param string $name The connection name (null for the default one)
     * @return \Doctrine\DBAL\Connection
     */
    public function getDatabaseConnection($name = null)
    {
        if ($name === null) {
            $name = $this->defaultConnection;
        }

        return $this->getDoctrine()->getConnection($name);
    }

    /**
     * @param string $name The connection name (null for the default one)
     * @return \Doctrine\DBAL\Driver\Connection
     */
    public function getDriver($name = null)
    {
        if ($name === null) {
            $name = $this->defaultConnection;
        }

        return $this->getDatabaseConnection($name)->getWrappedConnection();
    }

    /**
     * @return \Doctrine\Bundle\DoctrineBundle\Registry
     */
    public function getDoctrine()
    {
        return $this->getContainer()->get('doctrine');
    }

    /**
     * @param string $name The connection name (null for the default one)
     * @return \Doctrine\Common\Persistence\ObjectManager
     */
    public function getEntityManager($name = null)
    {
        if ($name === null) {
            $name = $this->defaultConnection;
        }

        return $this->getDoctrine()->getManager($name);
    }

    /**
     * @param string $className Repository class name.
     * @param string $name      The connection name (null for the default one)
     * @return \Doctrine\Common\Persistence\ObjectRepository
     */
    public function getRepository($className, $name = null)
    {
        if ($name === null) {
            $name = $this->defaultConnection;
        }

        return $this->getEntityManager($name)->getRepository($className);
    }

    // utils

    public function renderString($template, array $params = array())
    {
        if (!isset($this->myStringLoader)) {
            $this->myStringLoader = new \Twig_Environment(new \Twig_Loader_String());
        }

        return $this->myStringLoader->render($template, $params);
    }

    /**
     * Generates a URL from the given parameters.
     *
     * @param string  $route      The name of the route
     * @param mixed   $parameters An array of parameters
     * @param Boolean $absolute   Whether to generate an absolute URL
     *
     * @return string The generated URL
     */
    public function generateUrl($route, $parameters = array(), $absolute = false)
    {
        return $this->getContainer()->get('router')->generate($route, $parameters, $absolute);
    }

    protected function console($message)
    {
        $this->output->writeln(sprintf('%s %s %s', date('Y-m-d H:i:s'), $this->getName(), $message));
    }

    protected function startStamp()
    {
        $this->realUsage     = true;
        $this->startDateTime = microtime(true);
        $this->startPeakMem  = memory_get_peak_usage($this->realUsage);
    }

    protected function stamp()
    {
        $memUnit = 1024;

        return sprintf(
            'elapsed: %s sec, start peak mem: %s kB, end peak mem: %s kB',
            number_format(microtime(true) - $this->startDateTime),
            number_format($this->startPeakMem / $memUnit),
            number_format(memory_get_peak_usage($this->realUsage) / $memUnit)
        );
    }
}
