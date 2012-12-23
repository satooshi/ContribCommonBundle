<?php

namespace Contrib\CommonBundle\Service;

use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * Application mailer.
 *
 * example:
 *
 * [services.yml]
 *
 * services:
 *  my.mailer:
 *      class: %my.mailer.class%
 *      arguments: [@mailer, @router, @templating, %mailer_parameter%, @swiftmailer.transport.real]
 *
 * parameters:
 *  my.mailer.class: Contrib\CommonBundle\Service\Mailer
 *  mailer_parameter:
 *      member.registration.done.template: 'YourBundle:Registration:done.txt.twig'
 *      from_email:
 *          all: {%app.mail.from%: %app.mail.from.name%}
 *
 * [Controller class]
 *
 * class YourController extends Controller
 * {
 *     public function sendMailAction()
 *     {
 *         $name   = 'member.registration.done.template'; // must be defined in parameters.yml
 *         $email  = 'test@example.com';
 *         $mailer = $this->get('my.mailer');
 *         $mailer->send($name, $email);
 *     }
 * }
 */
class Mailer
{
    /**
     * Swift_Mailer.
     * @var Swift_Mailer
     */
    protected $mailer;

    /**
     * RouterInterface object.
     *
     * Assuming Symfony\Bundle\FrameworkBundle\Routing\Router object.
     *
     * @var RouterInterface
     */
    protected $router;

    /**
     * EngineInterface object.
     *
     * Assuming Symfony\Bundle\TwigBundle\TwigEngine object.
     *
     * @var EngineInterface
     */
    protected $templating;

    /**
     * Parameters defined by mailer_parameter.
     *
     * @var array
     */
    protected $parameters;

    /**
     * Swift_Transport_AbstractSmtpTransport.
     *
     * Assuming Swift_Transport_EsmtpTransport object.
     *
     * @var Swift_Transport_AbstractSmtpTransport
     */
    protected $realTransport;

    /**
     * Status of latest send mail (1: success).
     *
     * @var int
     */
    protected $status;

    /**
     * Sent message.
     *
     * @var \Swift_Message
     */
    protected $message;

    /**
     * Constructor.
     *
     * @param Swift_Mailer    $mailer
     * @param RouterInterface $router
     * @param EngineInterface $templating
     * @param array           $parameters
     * @param string          $realTransport
     */
    public function __construct($mailer, RouterInterface $router, EngineInterface $templating, array $parameters, $realTransport = null)
    {
        $this->mailer        = $mailer;
        $this->router        = $router;
        $this->templating    = $templating;
        $this->parameters    = $parameters;
        $this->realTransport = $realTransport;

        $this->configureRealTransport();
    }

    /**
     * Send email by using template.
     *
     * @param string $template
     * @param string $email
     * @param array  $parameters
     * @return int Send status.
     */
    public function send($template, $emailTo, array $parameters = array())
    {
        $template = $this->parameters[$template];
        $rendered = $this->templating->render($template, $parameters);

        return $this->sendMessage($rendered, $this->parameters['from_email']['all'], $emailTo);
    }

    /**
     * Send email from arguments.
     *
     * @param string $emailFrom
     * @param string $emailTo
     * @param string $subject
     * @param string $textBody
     * @param string $htmlBody
     * @return int Send status.
     */
    public function sendMail($emailFrom, $emailTo, $subject, $textBody, $htmlBody = null)
    {
        $this->message = $this->createTextMessage($subject, $emailFrom, $emailTo, $textBody);
        $this->status  = $this->mailer->send($this->message);

        return $this->status;
    }

    // internal method

    /**
     * Configure the argumet of helo command to [127.0.0.1] to avoid SwiftMailer bug which
     * causes that local domain may be rejected by mail server's configuration.
     *
     * @return void
     * @see http://stackoverflow.com/questions/4362417/swiftmailer-smtp-transport-rejecting-local-ip-address
     */
    protected function configureRealTransport()
    {
        if (!isset($this->realTransport))
        {
            return;
        }

        if ($this->realTransport instanceof \Swift_Transport_AbstractSmtpTransport)
        {
            $this->realTransport->setLocalDomain('[127.0.0.1]');
        }
    }

    /**
     * Send email from rendered template.
     *
     * @param string $renderedTemplate
     * @param string $emailFrom
     * @param string $emailTo
     * @return int Send status.
     */
    protected function sendMessage($renderedTemplate, $emailFrom, $emailTo)
    {
        $renderedLines = explode("\n", trim($renderedTemplate));
        $subject       = $renderedLines[0];
        $textBody      = implode("\n", array_slice($renderedLines, 1));

        return $this->sendmail($emailFrom, $emailTo, $subject, $textBody);
    }

    /**
     * Create a message for a japanese email.
     *
     * @param string $subject
     * @param string $emailFrom
     * @param string $emailTo
     * @param string $textBody
     * @return Swift_Message
     */
    protected function createJapaneseMessage()
    {
        return \Swift_Message::newInstance(null, null, null, 'iso-2022-jp')
        ->setCharset('iso-2022-jp')
        ->setEncoder(new \Swift_Mime_ContentEncoder_PlainContentEncoder('7bit'))
        ;
    }

    /**
     * Create a text message.
     *
     * @param string $subject
     * @param string $emailFrom
     * @param string $emailTo
     * @param string $textBody
     * @return Swift_Message
     */
    protected function createTextMessage($subject, $emailFrom, $emailTo, $textBody)
    {
        return $this->createJapaneseMessage()
        ->setFrom($emailFrom)
        ->setTo($emailTo)
        ->setSubject($subject)
        ->setBody($textBody)
        ;
    }

    /**
     * Create a multipart message.
     *
     * @param string $subject
     * @param string $emailFrom
     * @param string $emailTo
     * @param string $textBody
     * @param string $htmlBody
     * @return Swift_Message
     */
    protected function createMultipartMessage($subject, $emailFrom, $emailTo, $textBody, $htmlBody)
    {
        return $this->createTextMessage($subject, $emailFrom, $emailTo, $textBody)
        ->addPart($htmlBody, 'text/html')
        ;
    }

    // accessor

    public function getMailer()
    {
        return $this->mailer;
    }

    public function getRouter()
    {
        return $this->router;
    }

    public function getTemplating()
    {
        return $this->templating;
    }

    public function getParameters()
    {
        return $this->parameters;
    }

    public function getMessage()
    {
        if (isset($this->message))
        {
            return $this->message;
        }

        return null;
    }

    public function isSuccess()
    {
        return 1 === $this->status;
    }
}
