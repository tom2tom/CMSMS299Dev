<?php

namespace Ddrv\Mailer\Transport;

use Ddrv\Mailer\Contract\Transport;
use Ddrv\Mailer\Exception\RecipientsListEmptyException;
use Ddrv\Mailer\Message;
use RuntimeException;

final class SendmailTransport implements Transport
{
    /**
     * @var string[]
     */
    private $options;

    /**
     * @var Callable|null
     */
    private $requestLogger = null;

    /**
     * @var Callable|null
     */
    private $responseLogger = null;

    #[\ReturnTypeWillChange]
    public function __construct($options = array())
    {
        $this->options = $options;
    }
/*
    #[\ReturnTypeWillChange]
    public function __set($key, $value)
    {
        $this->options[$key] = $value;
    }

    #[\ReturnTypeWillChange]
    public function __get($key)
    {
        if (isset($this->options[$key])) return $this->options[$key];
        return null;
    }
*/
    /**
     * Note that escapeshellarg and escapeshellcmd alone are inadequate for
     * our purposes, especially on Windows.
     * This method is from PHPMailer.
     *
     * @param string $str command to be executed
     *
     * @return bool
     */
    protected function isShellSafe($str)
    {
        // Future-proof
        if (escapeshellcmd($str) !== $str ||
            !in_array(escapeshellarg($str), ["'$str'", "\"$str\""])) {
            return false;
        }

        for ($i = 0, $l = strlen($str); $i < $l; ++$i) {
            $c = $str[$i];
            // Fail if $str includes unwanted non-alphanum char(s),
            // which might have a special meaning.
            // '.' has a special meaning in cmd.exe, but its impact should be negligible here.
            // Non-Latin alphanum chars for the current locale are ok.
            if (!ctype_alnum($c) && strpos('@_-.', $c) === false) {
                return false;
            }
        }
        return true;
    }
/* from PHPMailer ....
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

        if ($this->SingleTo) {
            foreach ($this->SingleToArray as $toAddr) {
                $mail = @popen($sendmail, 'w');
                if (!$mail) {
                    throw new Exception($this->lang('execute') . $this->Sendmail, self::STOP_CRITICAL);
                }
                fwrite($mail, 'To: ' . $toAddr . "\n");
                fwrite($mail, $header);
                fwrite($mail, $body);
                $result = pclose($mail);
                $this->doCallback(
                    ($result === 0),
                    [$toAddr],
                    $this->cc,
                    $this->bcc,
                    $this->Subject,
                    $body,
                    $this->From,
                    []
                );
                if (0 !== $result) {
                    throw new Exception($this->lang('execute') . $this->Sendmail, self::STOP_CRITICAL);
                }
            }
        } else {
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

        RESULT LOGGER
        protected function doCallback($isSent, $to, $cc, $bcc, $subject, $body, $from, $extra)
        {
            if (!empty($this->action_function) && is_callable($this->action_function)) {
                call_user_func($this->action_function, $isSent, $to, $cc, $bcc, $subject, $body, $from, $extra);
            }
        }
*/
/* construct command :: refer to e.g. https://jpsoft.com/help/sendmail.htm
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
*/
/*
The sendmail command reads standard input for message text.

echo formatted message | sendmail [flags] [address ...]
e.g.
echo -e "Subject:Hello \n\n I would like to buy a hamburger\n" | sendmail email@example.com

sendmail [address] < file-containing-formatted-message

Subject: Hello
To: $TO

I would like to buy a hamburger

(
echo "From: ${from}";
echo "To: ${to}";
echo "Subject: ${subject}";
echo "Content-Type: text/html";
echo "MIME-Version: 1.0";
echo "";
echo "${message}";
) | sendmail -t

-F fullname Set the full name of the sender.

-t Sends the message to the recipients specified in the To:, Cc:, and Bcc: fields of the message header, as well as to any users specified on the command line.
The Bcc: line(s) are deleted before transmission


-O option=value Set option option to the specified value. This form uses long names. See below for more details.
-o option [value]   Set option option to the specified value. This form uses single character names only

Use the sendmail command only to deliver pre-formatted messages.


The sendmail command returns exit status values. These exit values (EX_*) are defined in the /usr/include/sysexits.h file.
EX_OK ....
*/
    /**
     * @inheritDoc
     */
    public function send(Message $message)
    {
        $subject = $message->getSubject();
        if (!$subject) {
            throw new RunTimeException("No subject was provided");
        }
        $to = $message->getRecipients();
        if (!$to) {
            throw new RecipientsListEmptyException();
        }
        $header = $message->getHeadersRaw();
        $body = $message->getBodyRaw();
        if ($body) {
            $s = wordwrap(trim($body), 70, "\r\n");
            // ensure correct line-breaks
            $body = preg_replace(array('/\r(?!\n)/', '/(?<!\r)\n/'), array('\r\n', '\r\n'), $s);
        } else {
            $body = '';
        }

        $mailer = $this->options['sendmailapp'];
/*
        $from = (!empty($this->options['sender'])) ? $this->options['sender'] : '';
        if ($from && $this->isShellSafe($from)) {
            if (strpos($mailer, 'qmail') !== false) {
                $template = '%s -f%s';
                $mailer = str_replace(array('-t','-f'), array('',''), $mailer);
            } else {
                $template = '%s -oi -f%s -t';
                $mailer = str_replace(array('-t','-oi'), array('',''), $mailer);
            }
            $command = sprintf($template, escapeshellcmd($mailer), $from);
        } else {
*/
            if (strpos($mailer, 'qmail') !== false) {
                $template = '%s';
                $mailer = str_replace(array('-t','-f'), array('',''), $mailer);
            } else {
                $template = '%s -oi -t';
                $mailer = str_replace(array('-t','-f','-oi'), array('','',''), $mailer);
            }
            $command = sprintf($template, escapeshellcmd($mailer));
//        }

        if (is_callable($this->requestLogger)) {
            $log = 'send (sendmail) to: '.$to; // TODO
            call_user_func($this->requestLogger, $log);
        }
/*
exec() only returns the last line of the generated output.
shell_exec() returns the full output of the command, when the command finished running.
system() immediately shows all output, and is used to show text.
*/
        $fh = @popen($command, 'wb');
        if ($fh) {
            fwrite($fh, $header);
            fwrite($fh, "\r\n\r\n");
            fwrite($fh, $body);
            fwrite($fh, "\r\n\r\n");
            $response = pclose($fh);
        } else {
            $response = 71;
        }

        if ($response == 0) {
            return true;
        }
        if ($response != 75) {
            // sendmail command result enum from BSD etc sysexits.h
            $errs = [
            64 => 'Command line usage error',
            65 => 'Data format error',
            66 => 'Cannot open input',
            67 => 'Username not recognized',
            68 => 'Hostname not recognized',
            69 => 'Necessary resources were not available',
            70 => 'Internal software error, including bad arguments',
            71 => 'System error',
            72 => 'Critical OS file missing',
            73 => 'Can\'t create (user) output file',
            74 => 'Input/output error',
            76 => 'Remote error in protocol',
            77 => 'Permission denied',
            78 => 'Configuration error',
//EX_SYNTAX ? => 'Syntax error in address',
            127 => 'Executable not found',
            ];
            $tmp = $errs[$response] ?? '';
            $tmp .= ' Email not sent successfully to '.implode(',',$to);
        } elseif (empty($this->options['delay-silent'])) {
            $tmp = 'Email not sent immediately to '.implode(',',$to);
        } else {
            return false;
        }
        if (is_callable($this->responseLogger)) {
            call_user_func($this->responseLogger, $tmp);
        }
        throw new RuntimeException($tmp);
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
