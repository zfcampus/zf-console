<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\Console;

use Exception;
use Zend\Console\Adapter\AdapterInterface as Console;
use Zend\Console\ColorInterface as Color;

class ExceptionHandler
{
    /**
     * @var Console
     */
    protected $console;

    /**
     * The message template to use when exceptions are handled.
     *
     * @var string
     */
    protected $messageTemplate;

    /**
     * @param Console $console
     */
    public function __construct(Console $console)
    {
        $this->console = $console;

        // Set default exception Message
        $this->messageTemplate = <<<EOT
======================================================================
   The application has thrown an exception!
======================================================================

 :className:
 :message
EOT;
    }

    /**
     * Set the message template to use.
     *
     * Message templates may define the following variable placeholders:
     *
     * - :className
     * - :message
     * - :code
     * - :file
     * - :line
     * - :stack
     * - :previous (this is used to report previous exceptions in a trace)
     *
     * @param mixed $messageTemplate
     */
    public function setMessageTemplate($messageTemplate)
    {
        $this->messageTemplate = (string) $messageTemplate;
    }

    /**
     * Handle an exception
     *
     * On completion, exits with a non-zero status code.
     *
     * @param Exception $exception
     */
    public function __invoke(Exception $exception)
    {
        $message = $this->createMessage($exception);

        $this->console->writeLine('Application exception: ', Color::RED);
        $this->console->write($message);
        $this->console->writeLine('');

        // Exceptions always indicate an error status; however, most have a
        // code of zero; set it to 1 in such cases.
        $exitCode = $exception->getCode();
        $exitCode = $exitCode ?: 1;
        exit($exitCode);
    }

    /**
     * Create the message to emit based on the provided exception and current message template
     *
     * @param Exception $exception
     * @return string
     */
    public function createMessage(Exception $exception)
    {
        $previous          = '';
        $previousException = $exception->getPrevious();
        while ($previousException) {
            $previous .= $this->fillTemplate($previousException, $previous);
            $previousException = $previousException->getPrevious();
        }

        return $this->fillTemplate($exception, $previous);
    }

    /**
     * Fill the message template with details of the given exception
     *
     * @param Exception $exception
     * @param false|string $previous If provided, adds the ":previous" template and this value
     * @return string
     */
    protected function fillTemplate(Exception $exception, $previous = false)
    {
        $templates = array(
            ':className',
            ':message',
            ':code',
            ':file',
            ':line',
            ':stack',
        );

        $replacements = array(
            get_class($exception),
            $exception->getMessage(),
            $exception->getCode(),
            $exception->getFile(),
            $exception->getLine(),
            $exception->getTraceAsString(),
        );

        if ($previous) {
            array_push($templates, ':previous');
            array_push($replacements, $previous);
        }

        $message = str_replace($templates, $replacements, $this->messageTemplate);

        // Strip unfilled ":previous" templates, if present
        if (! $previous) {
            return str_replace(':previous', '', $message);
        }

        return $message;
    }
}
