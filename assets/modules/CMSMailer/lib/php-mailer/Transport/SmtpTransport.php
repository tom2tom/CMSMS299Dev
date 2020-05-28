<?php

namespace Ddrv\Mailer\Transport;

use Closure;
use RuntimeException;
use Ddrv\Mailer\Exception\RecipientsListEmptyException;
use Ddrv\Mailer\Message;
use Ddrv\Mailer\TransportInterface;

final class SmtpTransport implements TransportInterface
{
    const ENCRYPTION_TLS = "tls";

    const ENCRYPTION_SSL = "ssl";

    /**
     * @var resource
     */
    private $socket;

    /**
     * @var string sender address
     */
    private $email;

    /**
     * @var Closure
     */
    private $logger;

    /**
     * @var string like [enctype://]hostname
     * PHP documentation refers to this as 'domain'
     * Not to be confused with the site host e.g. $_SERVER['SERVER_NAME']
     *  or gethostname() or php_uname('n')
     */
    private $connectHost;

    /**
     * @var int
     */
    private $connectPort;

    /**
     * @var string
     */
    private $connectUser;

    /**
     * @var string
     */
    private $connectPassword;

    /**
     * @var string
     * HELO/EHLO name
     */
    private $connectDomain;

    /**
     * @var int
     */
    private $responseTimeout;

    /**
     * @param Associative array of all interface-parameters
     * OR (old API) individual parameter-values:
     * @param string $host
     * @param int $port
     * @param string $user
     * @param string $pass
     * @param string $email
     * @param string $encryption optional default 'tls'
     * @param string $domain optional default $host
     * @param int $timeout NEW optional 10..300 seconds, default 60
     */
    public function __construct()
    {
        $args = func_get_args();
        if (count($args) == 1 && is_array($args[0])) {
            $vals = $args[0];
        } else {
            $vals = array(
            'host' => '',
            'port' => 0,
            'user' => '',
            'pass' => '',
            'email' => '',
            'encryption' => self::ENCRYPTION_TLS,
            'domain'  => '',
            'timeout' => 60,
            );
            $keys = array_keys($vals);
            foreach ($args as $i => $val) {
                $vals[$keys[$i]] = $val;
            }
        }

        $host = (string)$vals['host'];
        $port = (int)$vals['port'];
        if ($host && $port) {
            $domain = (string)$vals['domain'];
            if (!$domain) { $domain = $host; }
            $this->connectDomain = $domain;
            $encryption = strtolower($vals['encryption']);
            if (in_array($encryption, array(self::ENCRYPTION_TLS, self::ENCRYPTION_SSL))) {
                $host = "$encryption://$host";
            }
            $this->connectHost = $host;
            $this->connectPort = $port;
            $this->connectUser = (string)$vals['user'];
            $this->connectPassword = (string)$vals['pass'];
            $this->email = (string)$vals['email'];
            $val = (int)$vals['timeout'];
            $this->responseTimeout = min(max(10, $val), 300);
        }
    }

    public function __destruct()
    {
        //TODO handle persistent connection
        if (is_resource($this->socket)) {
            $this->smtpSend("QUIT", 5);
            fclose($this->socket);
        }
    }

    /**
     * @throws RuntimeException
     */
    private function connect()
    {
        if (is_resource($this->socket)) {
            return;
        }
        //TODO relevant timeout instead of 30
        $this->socket = fsockopen($this->connectHost, $this->connectPort, $errCode, $errMessage, 30);
        if (!is_resource($this->socket)) {
            throw new RuntimeException($this->connectHost." connection failed: ".$errMessage);
        }
        $info = stream_get_meta_data($this->socket);
        if ($info['eof']) {
            // socket is valid but not connected
            fclose($this->socket);
            throw new RuntimeException($this->connectHost." connection failure");
        }
        //TODO relevant timeout(s) instead of default
        if ($this->readStream() === false) {
            throw new RuntimeException($this->connectHost." connection: no response");
        }
        $this->smtpSend("EHLO {$this->connectDomain}"); // TODO try "EHLO $domain" then "HELO $domain" >> $response 250
        //TODO if using smtp without credentials
        $response = $this->smtpSend("AUTH LOGIN");
        if (strpos($response, "334") === false) {
            throw new RuntimeException("Email server credentials failure: ".$this->connectHost);
        }
        $this->smtpSend(base64_encode($this->connectUser));
        $response = $this->smtpSend(base64_encode($this->connectPassword));
        if (strpos($response, "235") === false) {
            throw new RuntimeException("Email server credentials failure: ".$this->connectHost);
        }
    }

    /**
     * @param Message $message
     * @return bool
     * @throws RecipientsListEmptyException
     */
/*   refer to e.g.
      https://stackoverflow.com/questions/2750211/sending-bcc-emails-using-a-smtp-server
      https://www.ibm.com/support/knowledgecenter/SSLTBW_2.2.0/com.ibm.zos.v2r2.aopu000/specifyingemailheaders.htm
      https://afterlogic.com/mailbee-net/docs/set_from_and_other_headers.html
*/
    public function send(Message $message)
    {
        $arr = $message->getRecipients();
        if (!$arr) {
            throw new RecipientsListEmptyException();
        }
        if (!$this->socket) {
            $this->connect();
        }
        //TODO relevant command-timeout(s) instead of default
        $this->smtpSend("MAIL FROM: <{$this->email}>");
        foreach ($arr as $address) {
            $this->smtpSend("RCPT TO: <$address>");
        }
        $this->smtpSend("DATA");
        $data = strtr($message->getRaw(false), array("\r\n"=>"\1", "\r"=>"\1", "\n"=>"\1"));
        $lines = explode("\1", $data);
        foreach ($lines as $str) {
            if (isset($str[0]) && $str[0] === '.') { $str = '.'.$str; }
            $this->smtpSend($str);
        }
        $response = $this->smtpSend("DATA END", $responseTimeout * 2);
        if (strpos($response, "250") !== false) {
            return true;
        }
        $msg = "Email not sent to ".$arr[0];
        if (count($arr) > 1) { $msg .= " etc (".count($arr)." addresses)"; }
        throw new RuntimeException($msg);
    }

    /**
     * @param Closure $logger
     */
    public function setLogger(Closure $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param string $text (to which "\r\n" will be appended before sending)
     * @param int $timeout seconds Optional, default the provided value
     * @return mixed string | false
     */
    private function smtpSend($text, $timeout = -1)
    {
        $response = false;
        if ($this->socket) {
            if (is_callable($this->logger)) {
                if (strlen($text > 30)) {
                    call_user_func($this->logger, "> ".substr($text, 0, 30)." ...");
                } else {
                    call_user_func($this->logger, "> $text");
                }
            }
            if ($this->writeStream($text."\r\n") !== false) {
                $response = $this->readStream($timeout);
                if (is_callable($this->logger)) {
                    call_user_func($this->logger, "< $response");
                    if (strncmp($response, "no response:", 12) == 0) { $response = false; } //TODO if no response
                }
            } elseif (is_callable($this->logger)) {
                call_user_func($this->logger, "< failure: command not transmitted");
            }
        }
        return $response;
    }

    private function readStream($timeout = -1)
    {
        if ($timeout < 0) {
            $timeout = $this->responseTimeout;
        } elseif ($timeout < 10) {
            $timeout = 10;
        }
        stream_set_timeout($this->socket, $timeout);
        $response = fgets($this->socket, 512);
        $info = stream_get_meta_data($this->socket);
        if (empty($info['timed_out'])) {
            return $response;
        }
        return "no response: timeout after $timeout seconds";
    }

    private function writeStream($text)
    {
        $total = strlen($text);
        $tries = (int)(ceil($total / 1000) * 10.01); // up to 10 tries per batch
        for ($sent = 0; $sent < $total, $tries > 0; $sent += $num, --$tries) {
            $batch = max(1000, $total - $sent); // <= 1000 bytes-per-write
            $num = fwrite($this->socket, substr($text, $sent, $batch));
            if ($num === false) {
                return false;
            }
        }
        return ($tries > 0) ? $sent : false;
    }
}
