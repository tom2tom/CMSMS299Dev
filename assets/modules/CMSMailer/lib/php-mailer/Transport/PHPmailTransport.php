<?php

namespace Ddrv\Mailer\Transport;

use Closure;
use mail;
use RuntimeException;
use Ddrv\Mailer\Exception\RecipientsListEmptyException;
use Ddrv\Mailer\Message;
use Ddrv\Mailer\TransportInterface;

final class PHPmailTransport implements TransportInterface
{
    /**
     * @var string
     */
    private $options;

    /**
     * @var Closure
     */
    private $logger;

    public function __construct()
    {
        $args = func_get_args();
        if (count($args) == 1 && is_array($args[0])) {
            $options = $args[0];
        } else {
            $options = array(
            //TODO default options
            );
            //$keys = array_keys($options);
            //foreach ($args as $i => $val) {
            //    $options[$keys[$i]] = $val;
            //}
        }
        $this->options = implode(" ", $options);
    }

    public function send(Message $message)
    {
        $subject = $message->getSubject();
        if (!$subject) {
            throw new RunTimeException("No subject was provided");
        }
        $arr = $message->getRecipients();
        if (!$arr) {
            throw new RecipientsListEmptyException();
        }
        $tmp = strtr($message->getBody(), array("\r\n"=>'\1', "\r"=>'\1', "\n"=>"\1"));
        $body = strtr($tmp, array("\1" => "\r\n"));
        // TODO multi-parts if appropriate
        $headers = implode("\r\n", $message->getHeaders());
        $to = implode(", ", $arr);
        if (is_callable($this->logger)) {
            $log = "mail(";
            $log .= "\"" . addslashes($to) . "\", ";
            $log .= "\"" . addslashes($subject) . "\", ";
            if (strlen($body) > 30) {
                $log .= "\"" . addslashes(substr($body, 0, 30) . " ...\", ";
            } else {
                $log .= "\"" . addslashes($body) . "\", ";
            }
            $log .= "\"" . addslashes($headers) . "\", ";
            $log .= "\"" . addslashes($this->options) . "\", ";
            $log .= ");";
            call_user_func($this->logger, $log);
        }
        if (mail($to, $subject, $body, $headers, $this->options)) {
            return true;
        }
        throw new RuntimeException("Email not sent successfully to ".$to);
    }

    public function setLogger(Closure $logger)
    {
        $this->logger = $logger;
    }
}
