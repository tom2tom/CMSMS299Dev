<?php

namespace Ddrv\Mailer\Transport;

use Closure;
use RuntimeException;
use Ddrv\Mailer\Exception\RecipientsListEmptyException;
use Ddrv\Mailer\Message;
use Ddrv\Mailer\TransportInterface;

final class SendmailTransport implements TransportInterface
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
        if (count($args) == 1 && is_array($args[0]) {
            $vals = $args[0];
        } else {
            //TODO merge $args into default $vals[]
            $vals = array(
            );
        }
        array_walk($args, function(&$value) { $value = trim($value); });
        $this->options = implode (" ", $vals);
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
        $tmp = strtr($message->getBody(), ["\r\n"=>'\1', "\r"=>'\1', "\n"=>'\1']);
        $body = strtr($tmp, ['\1' => "\r\n"]);
        $headers = implode("\r\n", $message->getHeaders());
        $to = implode(", ", $arr);
        // construct command :: refer to e.g. https://jpsoft.com/help/sendmail.htm
        $command = <<<EOS
path-to-sendmail [/= /A file1 [/A file2 ...]  /D /Eaddress /H"header: value" /In /IPv6 /M /Pn /R /Sn /SMTP=server /SSL[=n] /USER=address /V /X] "address[,address...] [cc:address[,address] bcc:address[,address...]]" subject [ text | @msgfile ]
EOS;
        if (is_callable($this->logger)) {
            $log = "sendmail(";  // TODO
            call_user_func($this->logger, $log);
        }
        exec($command ,$output, $result);
        if (0) { 
            return true;
        }
        throw new RuntimeException("Email not sent successfully to ".$to);
    }

    public function setLogger(Closure $logger)
    {
        $this->logger = $logger;
    }
}
