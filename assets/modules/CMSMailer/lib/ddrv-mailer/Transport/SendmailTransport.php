<?php

namespace Ddrv\Mailer\Transport;

use Ddrv\Mailer\Contract\Transport;
use Ddrv\Mailer\Exception\RecipientsListEmptyException;
use Ddrv\Mailer\Message;
use RuntimeException;

final class SendmailTransport implements Transport
{
    /**
     * @var string | string[]
     */
    private $options;

    /**
     * @var Callable|null
     */
    private $requestLogger;

    /**
     * @var Callable|null
     */
    private $responseLogger;


    public function __construct($options = '')
    {
        $this->options = $options;
    }

    /**
     * @inheritDoc
     */
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
        $body = strtr($tmp, ['\1' => "\r\n"]); //on WINOZE ??
        $headers = implode("\r\n", $message->getHeaders());
        $to = implode(", ", $arr);
        // construct command :: refer to e.g. https://jpsoft.com/help/sendmail.htm
        $command = <<<EOS
path-to-sendmail [/= /A file1 [/A file2 ...]
 /D
 /Eaddress
 /H"header: value"
 /In
 /IPv6
 /M
 /Pn
 /R
 /Sn
 /SMTP=server
 /SSL[=n]
 /USER=address /V /X] "address[,address...] [cc:address[,address] bcc:address[,address...]]"
 subject
 [ text | @msgfile ]
EOS;
        if (is_callable($this->requestLogger)) {
            $log = "sendmail(";  // TODO
            call_user_func($this->requestLogger, $log);
        }
/*
//static::$LE = e.g. self::CRLF
//self::STOP_CRITICAL =
    /**
     * Error severity: message, plus full stop, critical error reached.
     *
     * @var int
     * /
//    const STOP_CRITICAL = 2;

    /**
     * The SMTP standard CRLF line break.
     * If you want to change line break format, change static::$LE, not this.
     * /
//    const CRLF = "\r\n";

        $header = static::stripTrailingWSP($header) . static::$LE . static::$LE;

        // CVE-2016-10033, CVE-2016-10045: Don't pass -f if characters will be escaped.
        if (!empty($this->Sender) && self::isShellSafe($this->Sender)) {
            if ('qmail' === $this->Mailer) {
                $sendmailFmt = '%s -f%s';
            } else {
                $sendmailFmt = '%s -oi -f%s -t';
            }
        } elseif ('qmail' === $this->Mailer) {
            $sendmailFmt = '%s';
        } else {
            $sendmailFmt = '%s -oi -t';
        }

        $sendmail = sprintf($sendmailFmt, escapeshellcmd($this->Sendmail), $this->Sender);

            $mail = @popen($sendmail, 'w');
            if (!$mail) {
                throw new Exception($this->lang('execute') . $this->Sendmail, self::STOP_CRITICAL);
            }
            fwrite($mail, $header);
            fwrite($mail, $body);
            $result = pclose($mail);

            if ($result !== 0) {
                throw new Exception($this->lang('execute') . $this->Sendmail, self::STOP_CRITICAL);
            }
        }
        return true;
*/
        exec($command, $output, $result);
        if ($result == 0) {
            return true;
        }
/* result enum from BSD etc sysexits.h
EX_OK 0 Successful completion on all addresses.
EX_USAGE	64	command line usage error
EX_DATAERR	65	data format error
EX_NOINPUT	66	cannot open input
EX_NOUSER	67	username not recognized
EX_NOHOST	68	hostname not recognized
EX_UNAVAILABLE	69	catchall meaning necessary resources were not available.
EX_SOFTWARE	70	internal software error, including bad arguments.
EX_OSERR	71	system error (e.g., can't fork)
EX_OSFILE	72	critical OS file missing
EX_CANTCREAT	73	can't create (user) output file
EX_IOERR	74	input/output error
EX_TEMPFAIL	75	message could not be sent immediately, but was queued
EX_PROTOCOL	76	remote error in protocol
EX_NOPERM	77	permission denied
EX_CONFIG	78	configuration error
?? EX_SYNTAX 	? Syntax error in address.
*/
        if (is_callbable($this->responseLogger)) {
            call_user_func($this->responseLogger, 'TODO');
        }
        throw new RuntimeException("Email not sent successfully to ".$to);
    }

    /**
     * @param string $str command to be executed
     * @return bool
     */ 
    protected function isShellSafe($str)
    {
        if (escapeshellcmd($str) !== $str
          || !in_array(escapeshellarg($str), ["'$string'", "\"$string\""])) {
            return false;
        }

        // Fail if $str includes unwanted non-alphanum char(s), which might
        // have a special meaning.
        // '.' has a special meaning in cmd.exe, but its impact should be negligible here.
        // Non-Latin alphanum chars for the current locale are ok.
        for ($i = 0, $l = strlen($str); $i < $l; ++$i) {
            $c = $str[$i];
            if (!ctype_alnum($c) && strpos('@_-.', $c) === false) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param mixed $logger Callable | null
     */
    public function setRequestLogger($logger = null)
    {
        $this->requestLogger = $logger;
    }

    /**
     * @param mixed $logger Callable | null
     */
    public function setResponseLogger($logger = null)
    {
        $this->responseLogger = $logger;
    }
}
