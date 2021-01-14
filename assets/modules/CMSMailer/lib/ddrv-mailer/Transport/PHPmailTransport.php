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
    public function __set($key, $value)
    {
        $this->options[$key] = $value;
    }

    public function __get($key)
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
        $subject = $message->getSubject();
        $body = $message->getBodyRaw(); //TODO confirm $body not starts with multiple "\r\n"'s and each line separated by "\r\n"
        if ($body) { $body = trim($body)."\r\n"; }
        $headers = $message->getHeadersRaw(array('to','subject'));
        $parms = ($this->options) ? (is_array($this->options) ? implode(' ', $this->options) : $this->options) : '';
        if (is_callable($this->requestLogger)) {
            call_user_func($this->requestLogger, 'TODO');
        }
        if (mail($to, $subject, $body, $headers, trim($parms))) {
            return true;
        }
        $arr = error_get_last();
        if (is_callable($this->responseLogger)) {
            call_user_func($this->responseLogger, 'TODO ' . $arr['message']);
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
