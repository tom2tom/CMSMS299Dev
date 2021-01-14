<?php

namespace Ddrv\Mailer\Transport;

use Ddrv\Mailer\Contract\Transport;
use Ddrv\Mailer\Exception\TransportException;
use Ddrv\Mailer\Message;

final class SmtpTransport implements Transport
{

    const ENCRYPTION_TLS = 'tls';

    const ENCRYPTION_SSL = 'ssl';

    /**
     * @var resource
     */
    private $socket;

    /**
     * @var string sender address
     */
    private $sender;

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
     * @var string
     */
    private $transport;

    /**
     * @var int
     */
    private $responseTimeout = 25;

    /**
     * @var Callable|null
     */
    private $requestLogger = null;

    /**
     * @var Callable|null
     */
    private $responseLogger = null;

    /**
     * @param Associative array of all interface-parameters
     * OR (old API) individual parameter-values, as many of the following as needed:
     * @param string $host
     * @param int $port default 587
     * @param string $user
     * @param string $pass
     * @param string $sender email address
     * @param string $encryption optional default 'tls'
     * @param string $domain optional default same as $host
     * @param int $timeout since 5.1 optional 0 OR 5..120 seconds, default 25
     */
    public function __construct()
    {
        $vals = array(
         'host' => '',
         'port' => 587,
         'user' => '',
         'pass' => '',
         'sender' => '',
         'encryption' => self::ENCRYPTION_TLS,
         'domain'  => '',
         'timeout' => 25,
        );

        $args = func_get_args();
        if (count($args) == 1 && is_array($args[0])) {
            $vals = $args[0] + $vals;
        } else {
            $keys = array_keys($vals);
            foreach ($args as $i => $val) {
                $vals[$keys[$i]] = $val;
            }
        }
        if (!$vals['domain'] && $vals['host']) {
            $vals['domain'] = $vals['host'];
        } elseif (!$vals['host'] && $vals['domain']) {
            $vals['host'] = $vals['domain'];
        }

        $host = (string)$vals['host'];
        $port = (int)$vals['port'];
        if ($host && $port) {
            $encryption = trim(strtolower($vals['encryption']));
            switch ($encryption) {
                case self::ENCRYPTION_TLS:
                    $this->transport = $encryption;
                    break;
                case self::ENCRYPTION_SSL:
                    $this->transport = $encryption;
                    break;
                default:
                    $this->transport = 'tcp';
                    break;
            }
            $this->sender = (string)$vals['sender'];
            $this->connectHost = $this->transport . '://' . $host;
            $this->connectPort = $port;
            $this->connectUser = (string)$vals['user'];
            $this->connectPassword = (string)$vals['pass'];
            $this->connectDomain = (string)$vals['domain'];
            $val = (int)$vals['timeout'];
            if ($val !== 0) { $val = min(max(5, $val), 120); }
            $this->responseTimeout = $val;
        } else {
        //TODO signal failure
        }
        // reportedly, some hosts disable stream_socket_client()
        $this->streamer = function_exists('stream_socket_client');
    }
/*
    public function __set($key, $value)
    {
        $this->$key = $value;
    }

    public function __get($key)
    {
        if (isset($this->$key)) return $this->$key;
        return null;
    }
*/
    private function connect()
    {
        if (is_resource($this->socket)) {
            return;
        }
        if (!$this->connectHost) {
            return;
        }

        if ($this->streamer) {
            $addr = $this->connectHost . ':' . $this->connectPort;
            switch ($this->transport) {
                case self::ENCRYPTION_TLS:
                    $opts = ['tls' => [
//                    'allow_self_signed' => true,
                      'verify_host' => false,
                      'verify_peer' => false,
                      ],
                    ];
                    $context = stream_context_create($opts); //, $params);
                    break;
                case self::ENCRYPTION_SSL:
                    $opts = ['ssl' => [
//                    'allow_self_signed' => true,
                      'verify_host' => false,
                      'verify_peer' => false,
                      ],
                    ];
                    $context = stream_context_create($opts); //, $params);
                    break;
//              case 'tcp':
                default:
                    $context = stream_context_create();
                    break;
            }
            //TODO mailer persistent-connection setting
            $this->socket = stream_socket_client($addr, $errCode, $errMessage, $this->responseTimeout, STREAM_CLIENT_CONNECT, $context);
        } else { //stream_socket_client() N/A
            //NOTE blocking connection
            $this->socket = fsockopen($this->connectHost, $this->connectPort, $errCode, $errMessage, $this->responseTimeout);
        }
        if (!is_resource($this->socket)) {
            throw new TransportException('Connection Error: ' . $errCode . ' ' . $errMessage, 1);
        }
        stream_set_timeout($this->socket, $this->responseTimeout);

        $test = $this->readStream();
        //TODO handle error
/*          foreach ($test as $response) {
            $response['code'];
            $response['message'];
            $response['option'];
        }
*/
        unset($test);

        $options = $this->options();
        if ($this->transport == self::ENCRYPTION_TLS && array_key_exists('STARTTLS', $options)) {
            /*$res = */$this->smtpSend('STARTTLS');
            $mask = STREAM_CRYPTO_METHOD_TLSv1_0_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_1_CLIENT
                | STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_SSLv23_CLIENT;
            stream_socket_enable_crypto($this->socket, true, $mask);
            $options = $this->options();
        }
        $supported = array_key_exists('AUTH', $options) ? $options['AUTH'] : array();
        $types = array_intersect(array('PLAIN', 'LOGIN'), $supported);
        if ($types) {
            $this->auth(reset($types));
        }
    }

    private function auth($type)
    {
        switch ($type) {
            case 'PLAIN':
                $commands = array(base64_encode("\0" . $this->connectUser . "\0" . $this->connectPassword));
                break;
            case 'LOGIN':
                $commands = array(
                    base64_encode($this->connectUser),
                    base64_encode($this->connectPassword),
                );
                break;
            default:
                throw new TransportException('Unsupported auth type ' . $type, 3);
        }
        $this->smtpSend('AUTH ' . $type);
/*      $response = array(
            array(
                'code' => 500,
                'message' => 'Unknown error',
            )
        );
*/
        foreach ($commands as $command) {
            $response = $this->smtpSend($command);
        }
        if ($response[0]['code'] !== 235) {
            throw new TransportException($response[0]['message'], $response[0]['code']);
        }
    }

    /**
     * @inheritDoc
     */
    /* refer to e.g.
      https://www.ibm.com/support/knowledgecenter/SSLTBW_2.2.0/com.ibm.zos.v2r2.aopu000/specifyingemailheaders.htm
      https://afterlogic.com/mailbee-net/docs/set_from_and_other_headers.html
      https://stackoverflow.com/questions/2750211/sending-bcc-emails-using-a-smtp-server
     */
    public function send(Message $message)
    {
        if (!$this->socket) {
            $this->connect();
        }
        /*$res = */$this->smtpSend('MAIL FROM: <' . $this->sender . '>');
        foreach ($message->getRecipients() as $address) {
            /*$res = */$this->smtpSend('RCPT TO: <' . $address . '>');
        }
        /*$res = */$this->smtpSend('DATA');
        $data = $message->getHeadersRaw() . "\r\n\r\n" . $message->getBodyRaw() . "\r\n.\r\n";
        /*$res = */$this->smtpSend($data);
        return true;
    }

    /**
     * @param string $content
     * @return mixed array | bool (false)
     */
    private function smtpSend($content)
    {
        if (is_callable($this->requestLogger)) {
            if (strlen($content > 30)) {
                call_user_func($this->requestLogger, substr($content, 0, 30)." ...");
            } else {
                call_user_func($this->requestLogger, $content);
            }
        }
        $response = false;
        if ($this->writeStream($content . "\r\n") !== false) {
            $response = $this->readStream(-1);
            if (is_callable($this->responseLogger)) {
                call_user_func($this->responseLogger, $response);
                if (strncasecmp($response, "no response:", 12) == 0) {
                    $response = false; //TODO if no response
                }
            }
        } elseif (is_callable($this->requestLogger)) {
            call_user_func($this->requestLogger, "failure: command not transmitted");
        }
        return $response;
    }

    /**
     * @return mixed int | false
     */
    private function writeStream($content)
    {
        $total = strlen($content);
        $tries = (int)(ceil($total / 1000) * 10.01); // up to 10 tries per batch
        for ($sent = 0; $sent < $total && $tries > 0; $sent += $num, --$tries) {
            $batch = min(1000, $total - $sent); // up to 1000 bytes-per-write
            $num = fwrite($this->socket, substr($content, $sent, $batch));
            if ($num === false) {
                return false;
            }
        }
        return ($tries > 0) ? $sent : false;
    }

    /**
     * @return array[]
     */
    private function readStream($timeout = -1)
    {
        if ($timeout < 0) {
            $timeout = $this->responseTimeout;
        } elseif ($timeout < 5) {
            $timeout = 5;
        }
        stream_set_timeout($this->socket, $timeout);

        $response = fgets($this->socket, 512);
        do {
            $meta = stream_get_meta_data($this->socket);
            if (!empty($meta['timed_out'])) {
                return array(
                    'code' => 420,
                    'message' => "Nil or incomplete response: timeout after $timeout seconds",
                );
            }
            $unread = $meta['unread_bytes'];
            if ($unread) {
                $response .= fgets($this->socket, $unread + 512);
            }
        } while ($unread);

        if (is_callable($this->responseLogger)) {
            call_user_func($this->responseLogger, $response);
        }
        $stack = array();
        if ($response) {
            foreach (explode("\r\n", $response) as $line) {
                if ($line) {
                    $stack[] = array(
                        'code' => (int)substr($line, 0, 3),
                        'message' => substr($line, 4),
                        'option' => $line[3] === '-',
                    );
                }
            }
        }
        return $stack;
    }

    /**
     * @return array
     */
    private function options()
    {
        $options = array();
        $data = $this->smtpSend('EHLO ' . $this->connectDomain);
        if (!$data || $data[0]['code'] == 500) {
            $data = $this->smtpSend('HELO ' . $this->connectDomain);
        }
        foreach ($data as $row) {
            if ($row['option']) {
                $arr = explode(' ', $row['message']);
                $option = array_shift($arr);
                $options[$option] = $arr ? $arr : $option;
            }
        }
        return $options;
    }

    public function __destruct()
    {
        //TODO handle persistent connection
        if (is_resource($this->socket)) {
            /*$res = */$this->smtpSend('QUIT');
            fclose($this->socket);
        }
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
