<?php

namespace Ddrv\Mailer;

final class Message
{
    /**
     * @var string
     */
    private $from;

    /**
     * @var string
     */
    private $subject;

    /**
     * @var array
     */
    private $message = array(
        "plain" => null,
        "html" => null,
    );

    /**
     * @var array
     * Each member like lowercase("headerName")=>"headerName: headerValue"
     */
    private $headers = array();

    /**
     * @var array
     */
    private $attachments = array();

    /**
     * @var string[]
     */
    private $to = array();

    /**
     * @var string[]
     */
    private $cc = array();

    /**
     * @var string[]
     */
    private $bcc = array();

    /**
     * @var string
     */
    private $boundary;

    /**
     * @var bool
     */
    private $multi;

    public function __construct($subject = null, $html = null, $plain = null)
    {
        $mailer = sprintf("ddrv/mailer-%s (https://github.com/ddrv/mailer)", Mailer::MAILER_VERSION);
        $this->boundary = dechex(mt_rand(100, PHP_INT_SIZE << 10));
        $this->setHeaderRaw("MIME-Version", "1.0")
         ->setHeaderRaw("Content-Type", "multipart/mixed; boundary=\"r={$this->boundary}\"")
         ->setHeaderRaw("Content-Transfer-Encoding", "7bit")
         ->setHeaderRaw("X-Mailer", $mailer)
         ->setSubject($subject)
         ->setHtmlBody($html)
         ->setPlainBody($plain);

        $this->multi = function_exists("mb_strtolower");
    }

    public function setSubject($subject)
    {
        $this->subject = (string)$subject;
        return $this;
    }

    public function setHtmlBody($text)
    {
        if (is_null($text)) {
            $this->message["html"] = null;
        } else {
            $this->message["html"] = base64_encode($text);
        }
        return $this;
    }

    public function setPlainBody($text)
    {
        if (is_null($text)) {
            $this->message["plain"] = null;
        } else {
            $this->message["plain"] = base64_encode($text);
        }
        return $this;
    }

    public function addTo($email, $name = "")
    {
        $this->addAddress("to", $email, $name);
        return $this;
    }

    public function removeTo($email)
    {
        unset($this->to[$email]);
        return $this;
    }

    public function getTo()
    {
        return $this->to;
    }

    public function addCc($email, $name = "")
    {
        $this->addAddress("cc", $email, $name);
        return $this;
    }

    public function removeCc($email)
    {
        unset($this->cc[$email]);
        return $this;
    }

    public function getCc()
    {
        return $this->cc;
    }

    public function addBcc($email, $name = "")
    {
        $this->addAddress("bcc", $email, $name);
        return $this;
    }

    public function removeBcc($email)
    {
        unset($this->bcc[$email]);
        return $this;
    }

    public function getBcc()
    {
        return $this->bcc;
    }

    public function setFrom($email, $name = "")
    {
        $email = (string)$email;
        if (!$email) {
            return $this;
        }
        if (!$this->checkEmail($email)) {
            return $this;
        }
        $this->from = $this->getContact($email, $name);
        return $this;
    }

    public function getRecipients()
    {
        return array_keys(array_replace($this->to, $this->cc, $this->bcc));
    }

    public function setHeader($header, $value)
    {
        $this->setHeaderRaw($header, $this->headerEncode($value));
        return $this;
    }

    private function setHeaderRaw($header, $value)
    {
        $header = (string)$header;
        if ($value) {
            $key = ($this->multi) ? mb_strtolower($header) : strtolower($header);
            $tmp = strtr($value, array("\r\n"=>" ", "\r"=>" ", "\n"=>" "));
            $clean = strtr($tmp, array('  ' => ' '));
            $this->headers[$key] = "$header: $clean";
        } else {
            $this->removeHeader($header);
        }
        return $this;
    }

    public function removeHeader($header)
    {
        $key = ($this->multi) ? mb_strtolower($header) : strtolower($header);
        unset($this->headers[$key]);
        return $this;
    }

    public function attachFromString($name, $content, $mime = "application/octet-stream")
    {
        $name = $this->prepareAttachmentName($name);
        $this->attachments[$name] = array(
            "content" => base64_encode($content),
            "mime" => $mime,
        );
        return $this;
    }

    /**
     * @param $name
     * @param $path
     * @return $this
     */
    public function attachFromFile($name, $path)
    {
        $name = $this->prepareAttachmentName($name);
        $stream = fopen($path, "r");
        $content = "";
        if ($stream) {
            while (!feof($stream)) {
                $content .= fgets($stream, 4096);
            }
            fclose($stream);
        }
        if (!$content) {
            return $this->detach($name);
        }
        $mime = "application/octet-stream";
        if (function_exists("mime_content_type")) {
            /* @noinspection PhpComposerExtensionStubsInspection */
            $mime = mime_content_type($path);
        }
        $this->attachments[$name] = array(
            "content" => base64_encode($content),
            "mime" => $mime,
        );
        return $this;
    }

    public function detach($name)
    {
        $name = $this->prepareAttachmentName($name);
        if (array_key_exists($name, $this->attachments)) {
            unset($this->attachments[$name]);
        }
        return $this;
    }

    public function getSubject()
    {
        return $this->subject;
    }

    public function getHeaders($withbcc = true)
    {
        $this->setHeader("Subject", $this->subject);
        $this->setHeaderRaw("From", $this->from);
        $this->setHeaderTo();
        $this->setHeaderCc();
        if ($withbcc) $this->setHeaderBcc();
        $headers = array_values($this->headers);
        return $headers;
    }

    private function createBodyMessagePart()
    {
        $boundary = "b={$this->boundary}";
        $text = "\r\nContent-Type: multipart/alternative; boundary=\"$boundary\"\r\n\r\n";
        $parts = array();
        $parts[] = null;
        if (!is_null($this->message["plain"])) {
            $parts[] = $this->createBodyMessagePlainPart($this->message["plain"]);
        }
        if (!is_null($this->message["html"])) {
            $parts[] = $this->createBodyMessageHtmlPart($this->message["html"]);
        }
        $parts[] = "--";
        $text .= implode("--$boundary", $parts);
        return $text . "\r\n\r\n";
    }

    private function createBodyMessagePlainPart($content)
    {
        $text = "\r\nContent-type: text/plain; charset=UTF-8\r\n";
        $text .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $text .= chunk_split($content) . "\r\n";
        return $text;
    }

    private function createBodyMessageHtmlPart($content)
    {
        $boundary = "h={$this->boundary}";
        $text = "\r\nContent-Type: multipart/related; boundary=\"$boundary\"\r\n\r\n";
        $parts = array();
        $parts[] = null;

        $part = "\r\nContent-type: text/html; charset=UTF-8\r\n";
        $part .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $part .= chunk_split($content) . "\r\n"; //TODO mb_ safe version, then encode
        $parts[] = $part;
        $parts[] = "--";
        $text .= implode("--$boundary", $parts);
        return $text . "\r\n\r\n";
    }

    public function getBody()
    {
        $parts = array(
            null,
            $this->createBodyMessagePart(),
        );
        foreach ($this->attachments as $name => $attachment) {
            $part = "\r\nContent-type: {$attachment["mime"]}; name=$name\r\n";
            $part .= "Content-Transfer-Encoding: base64\r\n";
            $part .= "Content-Disposition: attachment\r\n\r\n";
            $part .= chunk_split($attachment["content"]) . "\r\n";  //TODO mb_ safe version, then encode
            $parts[] = $part;
        }
        $parts[] = "--";
        $body = implode("--r={$this->boundary}", $parts);
        return $body;
    }

    public function getRaw($withbcc = true)
    {
        $raw = implode("\r\n", $this->getHeaders($withbcc)) . "\r\n\r\n" . $this->getBody();
        return $raw;
    }

    public function getPersonalMessages()
    {
        $result = array();
        $recipients = array_replace($this->to, $this->cc, $this->bcc);
        foreach ($recipients as $email => $recipient) {
            $clone = clone $this;
            $clone->to = array(
                $email => $recipient,
            );
            $clone->cc = array();
            $clone->bcc = array();
            $result[] = $clone;
        }
        return $result;
    }

    /**
     * Return correct attachment name.
     *
     * @param $name
     * @return string
     */
    private function prepareAttachmentName($name)
    {
        $name = (string)$name;
        $name = preg_replace("/[\\\\\/\:\*\?\\\"<>]/ui", "_", $name);
        if (!$name) {
            $n = 1;
            do {
                $generated = "attachment_$n";
                $n++;
            } while (array_key_exists($generated, $this->attachments));
            $name = $generated;
        }
        return $name;
    }

    private function checkEmail($email)
    {
        //TODO Remove all characters except letters, digits and !#$%&'*+-=?^_`{|}~@.[]
        $arr = explode("@", $email);
        return (count($arr) == 2 && !empty($arr[0]) && !empty($arr[1]));
    }

    private function addAddress($type, $email, $name)
    {
        $email = (string)$email;
        if (!$email) {
            return false;
        }
        if (!$this->checkEmail($email)) {
            return false;
        }
        $contact = $this->getContact($email, $name);
        switch ($type) {
            case "to":
                $this->to[$email] = $contact;
                break;
            case "cc":
                $this->cc[$email] = $contact;
                break;
            case "bcc":
                $this->bcc[$email] = $contact;
                break;
        }
        return true;
    }

    private function setHeaderTo()
    {
        $this->setHeaderRaw("To", implode(", ", $this->to));
    }

    private function setHeaderCc()
    {
        $this->setHeaderRaw("Cc", implode(", ", $this->cc));
    }

    private function setHeaderBcc()
    {
        $this->setHeaderRaw("Bcc", implode(", ", $this->bcc));
    }

    private function getContact($email, $name = "")
    {
        $email = (string)$email;
        $name = preg_replace("/[^\pL\s,.\d]/ui", "", (string)$name);
        $name = trim($name);
        if (preg_match("/[^a-z0-9\s]+/ui", $name)) {
            $name = $this->headerEncode($name);
        }
        if ($name) {
            $name = "$name ";
        }
        return "$name<$email>";
    }

    public function serialize()
    {
        $raw = array(
            "subject" => $this->subject,
            "message" => $this->message,
            "headers" => $this->headers,
            "attachments" => $this->attachments,
            "to" => $this->to,
            "cc" => $this->cc,
            "bcc" => $this->bcc,
            "boundary" => $this->boundary,
        );
        return serialize($raw);
    }

    public function unserialize($serialized)
    {
        $raw = unserialize($serialized, array('allowed_classes' => false));
        $keys = array(
            "subject" => "",
            "message" => "",
            "headers" => array(),
            "attachments" => array(),
            "to" => array(),
            "cc" => array(),
            "bcc" => array(),
            "boundary" => md5(time()),
        );
        foreach ($keys as $key => $default) {
            $this->$key = array_key_exists($key, $raw) ? $raw[$key] : $default;
        }
    }

    private function headerEncode($value)
    {
        $value = mb_encode_mimeheader($value, "UTF-8", "B", "\r\n", 0);
        return $value;
    }
}
