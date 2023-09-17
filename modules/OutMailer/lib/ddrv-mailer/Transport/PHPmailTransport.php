<?php

namespace Ddrv\Mailer\Transport;

use Ddrv\Mailer\Contract\Transport;
use Ddrv\Mailer\Exception\RecipientsListEmptyException;
use Ddrv\Mailer\Message;
use Exception;

final class PHPmailTransport implements Transport
{
    /**
     * @var mixed strings[]
     */
    private $options;

    /**
     * @param mixed Callable | null
     */
    private $requestLogger = null;

    /**
     * @param mixed Callable | null
     */
    private $responseLogger = null;

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
            //string | strings[] | ...strings
            foreach ($args as /*$i => */$val) {
                //$options[$keys[$i]] = $val;
                $options[] = $val;
            }
        }
        $this->options = $options;
    }
/*
    public function __set(string $key, $value): void
    {
        $this->options[$key] = $value;
    }

    #[\ReturnTypeWillChange]
    public function __get(string $key) : mixed
    {
        if (isset($this->options[$key])) return $this->options[$key];
        return null;
    }
*/
    /**
     * @inheritDoc
     */
    public function send(Message $message)
    {
        $to = $message->getRecipientHeaders(Message::RECIPIENT_TO, true);
        if (!$to) {
            throw new RecipientsListEmptyException();
        }
        $to = str_replace('To:', '', $to);
        $subject = $message->getSubject(); // TODO ensure RFC 2047 compliance (http://www.faqs.org/rfcs/rfc2047.html)
        $body = $message->getBodyRaw();
        if ($body) {
            $s = wordwrap(trim($body), 70, "\r\n");
            // ensure correct line-breaks
            $s = preg_replace(array('/\r(?!\n)/', '/(?<!\r)\n/'), array("\r\n", "\r\n"), $s);
            $body = $s."\r\n";
        } else {
            $body = "\r\n";
        }
        $headers = $message->getHeadersRaw(array('to', 'subject')); //TODO support single-sending
        $parms = ($this->options) ? (is_array($this->options) ? implode(' ', $this->options) : $this->options) : '';
        if (is_callable($this->requestLogger)) {
            call_user_func($this->requestLogger, 'send (PHP mail) to: '.$to);
        }
//TODO support authentication using oAuth parameters from upstream
// when relevant - provider, type etc etc
        if (mail($to, $subject, $body, $headers, trim($parms))) {
            return true;
        }
        $arr = error_get_last();
        if (is_callable($this->responseLogger)) {
            call_user_func($this->responseLogger, 'failure: ' . $arr['message']);
        }
        throw new Exception($arr['message']);
    }

    /**
     * @param mixed Callable | null
     */
    public function setRequestLogger($logger = null)
    {
        $this->requestLogger = $logger;
    }

    /**
     * @param mixed Callable | null
     */
    public function setResponseLogger($logger = null)
    {
        $this->responseLogger = $logger;
    }
}
