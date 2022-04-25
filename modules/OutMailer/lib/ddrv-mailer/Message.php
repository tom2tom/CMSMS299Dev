<?php

namespace Ddrv\Mailer;

use Ddrv\Mailer\Contract\Message as MessageContract;
use Ddrv\Mailer\Exception\HeaderNotModifiedException;
use Ddrv\Mailer\Exception\InvalidAttachmentNameException;
use Ddrv\Mailer\Exception\InvalidEmailException;
use Ddrv\Mailer\Mailer;

final class Message implements MessageContract
{

    const RECIPIENT_TO = 'to';
    const RECIPIENT_CC = 'cc';
    const RECIPIENT_BCC = 'bcc';

    /**
     * @var string[]
     */
    private $headers;

    /**
     * @var array
     */
    private $recipients = array();

    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $boundary;

    /**
     * @var string
     */
    private $html;

    /**
     * @var string
     */
    private $text;

    /**
     * @var string
     * @since 5.1
     */
    private $charset;

    /**
     * @var string
     * "base64" | "quoted-printable" | "8bit" | "7bit" | "binary"
     * @since 5.1
     */
    private $encoding;

    /**
     * @var string
     * Intra-message newline char(s)
     * @since 5.1 TODO func(Transport type)
     */
    private $eol;

    /**
     * @var array
     * Populated by attachFrom*() methods
     * Each member like $name => ['content' => val, 'mime' => val]
     */
    private $attachments = array();

    /**
     * @var array
     * Populated by setHtmlContentFrom*() methods
     * Each member like $id => ['content' => val, 'mime' => val]
     * $id like <uuid@domain>
     */
    private $contents;

    /**
     * @var array
     */
    private $protectedHeaders = array(
        'subject' => array('setSubject($subject)', 'removeSubject()'),
        'from' => array('setSender($email, $name)', 'removeSender()'),
        'to' => array('addRecipient($email, $name, \'to\')', 'removeRecipients(\'to\')'),
        'cc' => array('addRecipient($email, $name, \'cc\')', 'removeRecipients(\'cc\')'),
        'bcc' => array('addRecipient($email, $name, \'bcc\')', 'removeRecipients(\'bcc\')'),
        'content-transfer-encoding' => null,
        'content-type' => null,
        'mime-version' => null,
        'message-id' => null,
    );

    /**
     * @var string[] Quoted-printable characters map
     */
    private $map = array(
        '=00', '=01', '=02', '=03', '=04', '=05', '=06', '=07', '=08', '=09', '=0A', '=0B', '=0C', '=0D', '=0E', '=0F',
        '=10', '=11', '=12', '=13', '=14', '=15', '=16', '=17', '=18', '=19', '=1A', '=1B', '=1C', '=1D', '=1E', '=1F',
        '=20', '=21', '=22', '=23', '=24', '=25', '=26', '=27', '=28', '=29', '=2A', '=2B', '=2C', '=2D', '=2E', '=2F',
        '=30', '=31', '=32', '=33', '=34', '=35', '=36', '=37', '=38', '=39', '=3A', '=3B', '=3C', '=3D', '=3E', '=3F',
        '=40', '=41', '=42', '=43', '=44', '=45', '=46', '=47', '=48', '=49', '=4A', '=4B', '=4C', '=4D', '=4E', '=4F',
        '=50', '=51', '=52', '=53', '=54', '=55', '=56', '=57', '=58', '=59', '=5A', '=5B', '=5C', '=5D', '=5E', '=5F',
        '=60', '=61', '=62', '=63', '=64', '=65', '=66', '=67', '=68', '=69', '=6A', '=6B', '=6C', '=6D', '=6E', '=6F',
        '=70', '=71', '=72', '=73', '=74', '=75', '=76', '=77', '=78', '=79', '=7A', '=7B', '=7C', '=7D', '=7E', '=7F',
        '=80', '=81', '=82', '=83', '=84', '=85', '=86', '=87', '=88', '=89', '=8A', '=8B', '=8C', '=8D', '=8E', '=8F',
        '=90', '=91', '=92', '=93', '=94', '=95', '=96', '=97', '=98', '=99', '=9A', '=9B', '=9C', '=9D', '=9E', '=9F',
        '=A0', '=A1', '=A2', '=A3', '=A4', '=A5', '=A6', '=A7', '=A8', '=A9', '=AA', '=AB', '=AC', '=AD', '=AE', '=AF',
        '=B0', '=B1', '=B2', '=B3', '=B4', '=B5', '=B6', '=B7', '=B8', '=B9', '=BA', '=BB', '=BC', '=BD', '=BE', '=BF',
        '=C0', '=C1', '=C2', '=C3', '=C4', '=C5', '=C6', '=C7', '=C8', '=C9', '=CA', '=CB', '=CC', '=CD', '=CE', '=CF',
        '=D0', '=D1', '=D2', '=D3', '=D4', '=D5', '=D6', '=D7', '=D8', '=D9', '=DA', '=DB', '=DC', '=DD', '=DE', '=DF',
        '=E0', '=E1', '=E2', '=E3', '=E4', '=E5', '=E6', '=E7', '=E8', '=E9', '=EA', '=EB', '=EC', '=ED', '=EE', '=EF',
        '=F0', '=F1', '=F2', '=F3', '=F4', '=F5', '=F6', '=F7', '=F8', '=F9', '=FA', '=FB', '=FC', '=FD', '=FE', '=FF',
    );

    /**
     * @var string[] Some common file-extensions mapped to MIME
     */
    private $mime = array(
        'txt' => 'text/plain',
        'htm' => 'text/html',
        'html' => 'text/html',
        'php' => 'text/html',
        'css' => 'text/css',
        'js' => 'application/javascript',
        'json' => 'application/json',
        'xml' => 'application/xml',
        'swf' => 'application/x-shockwave-flash',
        'flv' => 'video/x-flv',

        // images
        'png' => 'image/png',
        'jpe' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'jpg' => 'image/jpeg',
        'gif' => 'image/gif',
        'bmp' => 'image/bmp',
        'ico' => 'image/vnd.microsoft.icon',
        'tiff' => 'image/tiff',
        'tif' => 'image/tiff',
        'svg' => 'image/svg+xml',
        'svgz' => 'image/svg+xml',

        // archives
        'zip' => 'application/zip',
        'rar' => 'application/x-rar-compressed',
        'exe' => 'application/x-msdownload',
        'msi' => 'application/x-msdownload',
        'cab' => 'application/vnd.ms-cab-compressed',

        // audio/video
        'mp3' => 'audio/mpeg',
        'qt' => 'video/quicktime',
        'mov' => 'video/quicktime',

        // adobe
        'pdf' => 'application/pdf',
        'psd' => 'image/vnd.adobe.photoshop',
        'ai' => 'application/postscript',
        'eps' => 'application/postscript',
        'ps' => 'application/postscript',

        // ms office
        'doc' => 'application/msword',
        'rtf' => 'application/rtf',
        'xls' => 'application/vnd.ms-excel',
        'ppt' => 'application/vnd.ms-powerpoint',

        // open office
        'odt' => 'application/vnd.oasis.opendocument.text',
        'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
    );

    /**
     * @param string|null $subject
     * @param string|null $html
     * @param string|null $text
     */
    public function __construct($subject = null, $html = null, $text = null)
    {
        $this->id = $this->randomString(32, false);
        $this->boundary = '-+' . $this->randomString(12, false);
        $this->charset = 'UTF-8';
        $this->encoding = 'quoted-printable';
        $this->eol = "\r\n"; // TODO Except for smtp Transport, $this->eol = PHP_EOL;

        $this->headers = array(
            'mime-version' => '1.0',
            'message-id' => '<' . $this->id . '@>', //TODO format <uuid@domain>
            'content-type' => 'text/plain; charset=UTF-8',
            'content-transfer-encoding' => 'quoted-printable',
            'x-mailer' => 'ddrv/mailer-' . Mailer::MAILER_VERSION . ' (https://github.com/ddrv/php-mailer)',
        );
        $this->setSubject('' . $subject);
        $this->setHtml('' . $html);
        $this->setText('' . $text);
    }

    public function __clone()
    {
        $this->boundary = '-+' . $this->randomString(12, false);
        $this->id = $this->randomString(32, false);
        $this->setAnyHeader('message-id', '<' . $this->id . '@>'); //TODO format <uuid@domain>
        preg_match('/(?<name>.*)?<(?<email>[^>]+)>$/ui', (string)$this->getHeader('from'), $matches);
        if (array_key_exists('email', $matches)) {
            $arr = array_replace(array('', ''), explode('@', $matches['email']));
            $host = $arr[1];
            if ($host) {
                $this->setAnyHeader('message-id', '<' . $this->id . '@' . $host . '>');
            }
        }
    }

    /**
     * Set message (body, attachments) character-set
     * @since 5.1
     * @param mixed string|null $charset
     * @return self
     */
    public function setCharset($charset = 'UTF-8')
    {
        $value = $this->charset;
        $this->charset = ($charset) ? $charset : 'UTF-8';
        if ($value != $this->charset) {
            $this->headers['content-type'] = 'text/plain; charset=' . $this->charset;
        }
        return $this;
    }

    /**
     * Set message (body, attachments) transfer-encoding
     * @since 5.1
     * @param mixed string|null $encoding
     * @return self
     */
    public function setEncoding($encoding = 'quoted-printable')
    {
        $value = $this->encoding;
        $this->encoding = ($encoding) ? $encoding : 'quoted-printable';
        if ($value != $this->encoding) {
            $this->headers['content-transfer-encoding'] = $this->encoding;
        }
        return $this;
    }

    /**
     * @param string|null $subject Subject of message.
     * @return self
     */
    public function setSubject($subject)
    {
        $subject = (string)$subject;
        $this->setAnyHeader('subject', $subject);
        return $this;
    }

    /**
     * @param string|null $html HTML text of message.
     * @return self
     */
    public function setHtml($html = null)
    {
        if (!is_null($html)) {
            $html = trim((string)$html);
            if (!$html) {
                $html = null;
            }
        }
        $this->html = $html;
        $this->contents = array();
        $this->defineContentType();
        return $this;
    }

    /**
     * @param string|null $text Plain text of message.
     * @return self
     */
    public function setText($text = null)
    {
        if (!is_null($text)) {
            $text = trim((string)$text);
            if (!$text) {
                $text = null;
            }
        }
        $this->text = $text;
        $this->defineContentType();
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setSender($email, $name = null)
    {
        $email = (string)$email;
        if (!$email) {
            $this->setAnyHeader('message-id', '<' . $this->id . '@>'); //TODO format <uuid@domain>
            return $this;
        }
        $this->checkEmail($email);
        $parts = explode('@', $email, 2);
        $id = $this->id . '@';
        if ($parts[1]) {
            $id .= $parts[1];
        } else {
            $id .= $parts[0]; //TODO format <uuid@domain>
        }
        $this->setAnyHeader('message-id', '<' . $id . '>');
        $contact = $this->getContact($email, $name);
        $this->setAnyHeader('from', $contact);
        return $this;
    }

    /**
     * @param string $email Recipient email.
     * @param string|null $name Recipient name.
     * @param string $type Recipient type. May be 'to', 'cc' or 'bcc'. Default 'to'.
     * @return self
     * @throws InvalidEmailException
     */
    public function addRecipient($email, $name = null, $type = self::RECIPIENT_TO)
    {
        $type = strtolower((string)$type);
        if (!in_array($type, array(self::RECIPIENT_CC, self::RECIPIENT_BCC))) {
            $type = self::RECIPIENT_TO;
        }
        $email = (string)$email;
        $this->checkEmail($email);
        $this->recipients[$email] = array(
            'header' => $type,
            'name' => $name,
        );
        return $this;
    }

    /**
     * @param string $email Recipient email.
     * @return string|null Recipient name or null.
     */
    public function getRecipientName($email)
    {
        if (!array_key_exists($email, $this->recipients)) {
            return null;
        }
        return $this->recipients[$email]['name'];
    }

    /**
     * @param string $email Recipient email.
     * @return self
     */
    public function removeRecipient($email)
    {
        $email = (string)$email;
        if (array_key_exists($email, $this->recipients)) {
            unset($this->recipients[$email]);
        }
        return $this;
    }

    /**
     * @param mixed $type string | null Recipient type. May be 'to', 'cc', 'bcc' or null. Default null.
     * @return self
     */
    public function removeRecipients($type = null)
    {
        $type = strtolower((string)$type);
        if (!in_array($type, array(self::RECIPIENT_TO, self::RECIPIENT_CC, self::RECIPIENT_BCC))) {
            $type = '';
        }
        if (!$type) {
            $this->recipients = array();
            return $this;
        }
        foreach ($this->recipients as $email => $recipient) {
            if ($recipient['header'] === $type) {
                unset($this->recipients[$email]);
            }
        }
        return $this;
    }

    /**
     * @param string $name
     * @param string $content
     * @param string|null $mime
     * @return self
     * @throws InvalidAttachmentNameException
     */
    public function attachFromString($name, $content, $mime = null)
    {
        $content = (string)$content;
        $name = $this->prepareAttachmentName($name); // maybe $name with appended (N)
        if (!$mime) {
            $mime = $this->detectMimeType($content);
        }
        $this->attachments[$name] = array(
            'content' => base64_encode($content), //TODO per transfer-encoding
            'mime' => $mime,
        );
        $this->defineContentType();
        return $this;
    }

    /**
     * @param string $name
     * @param string $path
     * @param string|null $mime
     * @return self
     */
    public function attachFromFile($name, $path, $mime = null)
    {
        if (file_exists($path)) {
            $content = file_get_contents($path);
        } else {
            $content = '';
        }
        if (!$mime) {
            $mime = $this->detectMimeType($content);
        }
        if ($mime === 'application/octet-stream') {
            $mime = $this->detectMimeType($path);
        }
        return $this->attachFromString($name, $content, $mime);
    }

    /**
     * @param string $name
     * @return self
     */
    public function detach($name)
    {
        //TODO handle attachment named like $name(N)
        if (array_key_exists($name, $this->attachments)) {
            unset($this->attachments[$name]);
        }
        $this->defineContentType();
        return $this;
    }

    /**
     * @param string $id
     * @param string $content
     * @param string $mime
     * @return self
     */
    public function setHtmlContentFromString($id, $content, $mime = 'application/octet-stream')
    {
        $content = (string)$content;
        $id = $this->prepareContentId($id);
        if (!$mime) {
            $mime = $this->detectMimeType($content);
        }
        $this->contents[$id] = array(
            'content' => base64_encode($content), //TODO per transfer-encoding
            'mime' => $mime,
        );
        $this->defineContentType();
        return $this;
    }

    /**
     * @param string $id
     * @param string $path
     * @param string $mime
     * @return self
     */
    public function setHtmlContentFromFile($id, $path, $mime = 'application/octet-stream')
    {
        if (file_exists($path)) {
            $content = file_get_contents($path);
        } else {
            $content = '';
        }
        if (!$mime) {
            $mime = $this->detectMimeType($content);
        }
        if ($mime === 'application/octet-stream') {
            $mime = $this->detectMimeType($path);
        }
        return $this->setHtmlContentFromString($id, $content, $mime);
    }

    /**
     * @param string $id
     * @return self
     */
    public function unsetHtmlContent($id)
    {
        $id = $this->prepareContentId($id);
        if (array_key_exists($id, $this->contents)) {
            unset($this->contents[$id]);
        }
        $this->defineContentType();
        return $this;
    }

    /** Deprecated alias */
    public function unsetBodyHtmlContent($id)
    {
        return $this->unsetHtmlContent($id);
    }

    /**
     * @param string $header Header name.
     * @param string|null $value Header values.
     * @return self
     */
    public function setHeader($header, $value)
    {
        $value = trim((string)$value);
        if (!$value) {
            return $this->removeHeader($header);
        }
        $this->touchHeader($header, false);
        $this->setAnyHeader($header, $value);
        return $this;
    }

    /**
     * @param string $header Header name.
     * @return bool true if exists.
     */
    public function hasHeader($header)
    {
        $header = $this->prepareHeaderName($header);
        return array_key_exists($header, $this->headers);
    }

    /**
     * @param string $header Header name.
     * @return string|null Header value.
     */
    public function getHeader($header)
    {
        $header = $this->prepareHeaderName($header);
        return array_key_exists($header, $this->headers) ? $this->headers[$header] : null;
    }

    /**
     * @param string $header Header name.
     * @return self
     */
    public function removeHeader($header)
    {
        $this->touchHeader($header, true);
        $this->removeAnyHeader($header);
        return $this;
    }

    /**
     * Get body, attachments character-set
     * @since 5.1
     * @return string
     */
    public function getCharset()
    {
        return $this->charset;
    }

    /**
     * Get body, attachments transfer-encoding
     * @since 5.1
     * @return string
     */
    public function getEncoding()
    {
        return $this->encoding;
    }

    /**
     * @since 5.1
     * @return array
     */
    public function getHeaders()
    {
       return $this->headers;
    }

    /**
     * @since 5.1
     * @param mixed $type string | null Recipient type. May be 'to', 'cc', 'bcc' or null. Default null.
     * @param bool $raw optional flag whether to return ... Default false.
     * @return mixed array (possibly empty) | string if $raw is true
     */
    public function getRecipientHeaders($type = null, $raw = false)
    {
        $type = strtolower((string)$type);
        if (!in_array($type, array(self::RECIPIENT_TO, self::RECIPIENT_CC, self::RECIPIENT_BCC))) {
            $type = '';
        }
        $arr = array();
        foreach ($this->recipients as $email => $recipient) {
            if (array_key_exists('header', $recipient) && (!$type || $recipient['header'] === $type)) {
                $arr[$email] = $recipient;
            }
        }
        if ($raw) {
            $headers = array();
            $parts = array();
            foreach ($arr as $email => $recipient) {
                $headers[$recipient['header']] = '';
                $parts[$recipient['header']][] = $this->getContact($email, $recipient['name']);
            }
            foreach ($headers as $key => &$value) {
               $value = $this->encodeHeader(ucfirst($key), implode(', ', $parts[$key]));
            }
            unset ($value);
            return implode($this->eol, $headers); //TODO eol = func(Transport type )
        }
        return $arr;
    }

    /**
     * @return string[] all Recipients' emails.
     */
    public function getRecipients()
    {
        return array_keys($this->recipients);
    }

    /**
     * @return string|null
     */
    public function getSubject()
    {
        return $this->getHeader('subject');
    }

    /**
     * @param mixed string|strings[] since 5.1 optional name(s) of unwanted headers
     * @return string Raw string as email headers
     */
    public function getHeadersRaw($except = '')
    {
        if ($except && is_array($except)) {
            $omits = implode (' ', $except);
        } else {
            $omits = (string)$except;
        }

        $headers = array();
        if (!$omits || stripos($omits, 'date') === false) {
            $headers[] = $this->encodeHeader($this->normalizeHeaderName('date'), date("D, d M Y H:i:s O"));
        }
        foreach ($this->headers as $name => $line) {
            if ($omits && stripos($omits, $name) !== false) {
                continue;
            }
            $header = $this->normalizeHeaderName($name);
            $values = explode(',', $line);
            foreach ($values as $value) {
                $value = trim($value);
                if (!$value) {
                    continue;
                }
                $headers[] = $this->encodeHeader($header, $value);
            }
        }
        foreach ($this->recipients as $email => $recipient) {
            if (
                !array_key_exists('header', $recipient)
                || !is_string($recipient['header'])
                || !in_array($recipient['header'], array(self::RECIPIENT_TO, self::RECIPIENT_CC, self::RECIPIENT_BCC))
                || ($omits && stripos($omits, $recipient['header']) !== false)
            ) {
                continue;
            }
            $value = $this->getContact($email, $recipient['name']);
            $headers[] = $this->encodeHeader(ucfirst($recipient['header']), $value);
        }
        return implode($this->eol, $headers); //TODO eol = func(Transport type)
    }

    /**
     * @inheritDoc
     */
    public function getBodyRaw()
    {
        $info = $this->getBodyInfo(false);
        return $info['data'];
    }

    public function getPersonalMessages()
    {
        $messages = array();
        foreach ($this->getRecipients() as $recipient) {
            $name = $this->getRecipientName($recipient);
            $new = clone $this;
            $messages[] = $new->removeRecipients()->addRecipient($recipient, $name);
        }
        return $messages;
    }

    //PHP 8.1+ Serializable interface compatibility
	public function __serialize() : array
	{
        return [
            'id' => $this->id,
            'headers' => $this->headers,
            'boundary' => $this->boundary,
            'html' => $this->html,
            'text' => $this->text,
            'attachments' => $this->attachments,
            'contents' => $this->contents,
            'recipients' => $this->recipients,
            'charset' => $this->charset,
            'encoding' => $this->encoding,
            'eol' => $this->eol,
        ];
	}

	public function __unserialize(array $data) : void
	{
        $empty = [
            'id' => array(),
            'headers' => array(),
            'boundary' => '',
            'html' => '',
            'text' => '',
            'attachments' => array(),
            'contents' =>  array(),
            'recipients' => array(),
            'charset' => '',
            'encoding' => '',
            'eol' => '',
        ];
        foreach ($empty as $key => $default) {
            $this->$key = array_key_exists($key, $data) ? $data[$key] : $default;
        }
	}

    /**
     * @inheritDoc
     */
//    public function serialize() : ?string PHP 8+
    public function serialize()
    {
        return \serialize($this->__serialize());
    }

    /**
     * @inheritDoc
     */
//    public function unserialize(string $serialized) : void PHP 8+
    public function unserialize($serialized)
    {
        $this->__unserialize(\unserialize($serialized));
    }

    /**
     * @param bool $onlyType
     * @return array
     */
    private function getBodyInfo($onlyType)
    {
        $main = $this->getMainInfo($onlyType);
        if (empty($this->attachments)) {
            return $main;
        }
        $result = array(
            'type' => '',
            'data' => '',
        );
        $result['type'] = 'multipart/mixed; boundary="mail=' . $this->boundary . '"';
        if ($onlyType) {
            return $result;
        }
        $eol = $this->eol; //TODO eol = func(Transport type)
        $body = $eol
         . $this->encodeHeader('Content-Type', $main['type']) . $eol
         . 'Content-Transfer-Encoding: ' . $this->encoding . $eol
         . $eol
         . $main['data'];
        $parts = array(null, $body);
        foreach ($this->attachments as $name => $attachment) {
            $part = $eol
             . $this->encodeHeader('Content-Type', $attachment['mime'] . '; name=' . $name) . $eol
             . $this->encodeHeader('Content-Disposition', 'attachment; filename=' . $name) . $eol
             . 'Content-Transfer-Encoding: base64' . $eol //TODO per $this->encoding
             . $eol
             . chunk_split($attachment['content']);
            $parts[] = $part;
        }
        $parts[] = '--';
        $result['data'] = implode('--mail=' . $this->boundary, $parts) . $eol;
        return $result;
    }

    /**
     * @param bool $onlyType
     * @return array
     */
    private function getMainInfo($onlyType)
    {
        $result = array(
            'type' => '',
            'data' => '',
        );
        if (is_null($this->html) && is_null($this->text)) {
            return $result;
        }
        $htmlInfo = $this->getHtmlInfo($onlyType);
        if (!is_null($this->html) && is_null($this->text)) {
            return $htmlInfo;
        }
        if (!is_null($this->text) && is_null($this->html)) {
            $result['type'] = 'text/plain; charset=' . $this->charset;
            if (!$onlyType) {
                $result['data'] = quoted_printable_encode($this->text);
            }
            return $result;
        }
        $mixed = false;
        if (!is_null($this->text) && !is_null($this->html)) {
            $result['type'] = 'multipart/alternative; boundary="body=' . $this->boundary . '"';
            $mixed = true;
        }
        if ($onlyType) {
            return $result;
        }
        if (!$mixed) {
            $result['data'] = $htmlInfo['data'];
            return $result;
        }

        $eol = $this->eol; //TODO eol = func(Transport type)
        $text = $eol
         . 'Content-Type: text/plain; charset=' . $this->charset . $eol
         . 'Content-Transfer-Encoding: ' . $this->encoding . $eol
         . $eol
         . quoted_printable_encode($this->text) . $eol;

        $html = $eol
         . $this->encodeHeader('Content-Type', $htmlInfo['type']) . $eol
         . 'Content-Transfer-Encoding: ' . $this->encoding . $eol
         . $eol
         . $htmlInfo['data'] . $eol;

        $parts = array(null, $text, $html, '--');
        $result['data'] = implode('--body=' . $this->boundary, $parts) . $eol;
        return $result;
    }

    /**
     * @param bool $onlyType
     * @return array
     */
    private function getHtmlInfo($onlyType)
    {
        $mixed = false;
        $result = array(
            'type' => '',
            'data' => '',
        );
        if (is_null($this->html)) {
            return $result;
        }
        $raw = quoted_printable_encode($this->html);
        if (empty($this->contents)) {
            $result['type'] = 'text/html; charset=' . $this->charset;
        } else {
            $result['type'] = 'multipart/related; boundary="html=' . $this->boundary . '"';
            $mixed = true;
        }
        if ($onlyType) {
            return $result;
        }
        if (!$mixed) {
            $result['data'] = $raw;
            return $result;
        }
        $eol = $this->eol; //TODO eol = func(Transport type)
        $html = $eol
         . 'Content-Type: text/html; charset=' . $this->charset . $eol
         . 'Content-Transfer-Encoding: ' . $this->encoding . $eol
         . $eol
         . $raw . $eol;
        $parts = array(null, $html);
        foreach ($this->contents as $id => $content) {
            $cid = $id . '@'; //TODO $cid format like X@Y
            $part = $eol
             . $this->encodeHeader('Content-Type', $content['mime'] . '; name=' . $id) . $eol
             . 'Content-Transfer-Encoding: base64' . $eol //TODO per $this->encoding
             . 'Content-Disposition: inline' . $eol
             . $this->encodeHeader('Content-ID', '<' . $cid . '>') . $eol . $eol
             . chunk_split($content['content']);
            $parts[] = $part;
        }
        $parts[] = '--';
        $result['data'] = implode('--html=' . $this->boundary, $parts) . $eol;
        return $result;
    }

    /**
     * @void
     */
    private function defineContentType()
    {
        $info = $this->getBodyInfo(true);
        $this->setAnyHeader('content-type', $info['type']);
    }

    /**
     * @param int $length Default 32
     * @param book $hex whether to generate a hexadecimal string Default true (otherwise approximately base-64)
     * @return string
     */
    private function randomString($length = 32, $hex = true)
    {
        $bc = ($hex) ? (int)($length / 2) + 1 : (int)($length * 3 / 4) + 1;
        $str = str_repeat(' ', $bc);
        for ($i = 0; $i < $bc; ++$i) {
            $str[$i] = chr(mt_rand(1, 255));
        }
        $value = ($hex) ? bin2hex($str) :
            strtr(base64_encode($str), '/=', '--'); //suitable for a boundary identifier
        return substr($value, 0, $length);
    }

    /**
     * @param string $header Header name.
     * @param string $value Header values.
     * @return self
     */
    private function setAnyHeader($header, $value)
    {
        $header = $this->prepareHeaderName($header);
        if (!$header) {
            return $this;
        }
        $value = $this->prepareHeaderValue($value);
        if ($value) {
            $this->headers[$header] = $value;
        } else {
            if (array_key_exists($header, $this->headers)) {
                unset($this->headers[$header]);
            }
        }
        return $this;
    }

    /**
     * @param string $header Header name.
     * @return string|null Header values.
     */
    private function removeAnyHeader($header)
    {
        $header = $this->prepareHeaderName($header);
        if (array_key_exists($header, $this->headers)) {
            unset($this->headers[$header]);
        }
        return $this;
    }

    /**
     * @param string $header
     * @param bool $removing
     * @return bool
     * @throws HeaderNotModifiedException
     */
    private function touchHeader($header, $removing)
    {
        $header = $this->prepareHeaderName($header);
        if (array_key_exists($header, $this->protectedHeaders)) {
            $removing = (bool)$removing;
            $key = (int)$removing;
            $method = is_array($this->protectedHeaders[$header]) ? $this->protectedHeaders[$header][$key] : null;
            throw new HeaderNotModifiedException($header, $method);
        }
        return true;
    }

    /**
     * @param string $name
     * @return string
     */
    private function normalizeHeaderName($name)
    {
        if ($name === 'mime-version') {
            return 'MIME-Version';
        }
        if ($name === 'message-id') {
            return 'Message-ID';
        }
        $name = preg_replace_callback(
            '/(^|-)[a-z]/ui',
            function ($match) {
                return strtoupper($match[0]);
            },
            $name
        );
        return $name;
    }

    /**
     * @param string $name (normally ASCII) identifier
     * @return string
     */
    private function prepareHeaderName($name)
    {
        $value = $this->prepareHeaderValue($name);
        if (function_exists('mb_strtolower')) {
            return mb_strtolower($value);
        }
        return strtolower($value);
    }

    /**
     * @param string $name
     * @return string
     */
    private function prepareHeaderValue($name)
    {
        $value = strtr(trim((string)$name), array("\r\n" => ' ', "\n" => ' ', "\r" => ' '));
        return preg_replace('/\s{2,}/', ' ', $value);
    }

    /**
     * @param string $email
     * @param bool $checkDNS Default false
     * @return void
     * @throws InvalidEmailException
     */
    private function checkEmail($email, $checkDNS = false)
    {
        //PHP's FILTER_VALIDATE_EMAIL mechanism is not entirely reliable - see notes at https://www.php.net/manual/en/function.filter-var.php
        $email = (string)$email;
        $email = trim('' . $email);
        if (!preg_match('/\S+.*@[\w.\-\x80-\xff]+$/', $email)) {
            throw new InvalidEmailException($email);
        }
        if ($checkDNS && function_exists('checkdnsrr')) {
            list($u, $h) = explode('@', $email, 2);
            if (!(checkdnsrr($h, 'A') || checkdnsrr($h, 'MX'))) {
                // domain doesn't exist
                throw new InvalidEmailException($email);
            }
        }
    }

    /**
     * @param string $email
     * @param string|null $name
     * @return string
     */
    private function getContact($email, $name = '')
    {
        $email = (string)$email;
        $name = preg_replace('/[^\w\pL\s,.]/u', '', trim($name));
        if ((strpos($name, ' ') !== false || strpos($name, "\t") !== false) && $name) {
            $name = '"' . $name . '"';
        }
        if ($name) {
            return $name . ' <' . $email . '>';
        }
        return $email;
    }

    /**
     * Return unique attachment name, possibly with appended '(N)'.
     *
     * @param $name
     * @throws InvalidAttachmentNameException
     * @return string
     */
    private function prepareAttachmentName($name)
    {
        $name = (string)$name;
        if (!$name || preg_match('/[\\/*?"<>\\\\]/ui', $name)) {
            throw new InvalidAttachmentNameException($name);
        }
        if (array_key_exists($name, $this->attachments)) {
            $n = 0;
            do {
                $generated = $name . '(' . ++$n . ')';
            } while (array_key_exists($generated, $this->attachments));
            $name = $generated;
        }
        return $name;
    }

    /**
     * Return correct attachment name.
     *
     * @param $name
     * @throws InvalidAttachmentNameException
     * @return string
     */
    private function prepareContentId($name)
    {
        $name = (string)$name;
        return $name; //TODO want format like '<uuid@domain>'
    }

    /**
     * @param string $header
     * @param string $value
     * @return string
     */
    private function encodeHeader($header, $value)
    {
        $max = 74;
        $offset = strlen($header) + 2;
        $symbols = str_split($value);
        unset($value);
        $result = $header . ': ';
        $coding = false;
        $encoder = '=?'.$this->charset.'?Q?';
        $all = count($symbols);
        $position = $offset;
        foreach ($symbols as $num => $symbol) {
            $line = '';
            $add = 0;
            $char = ord($symbol);
            $ascii = ($char >= 32 && $char <= 60) || ($char >= 62 && $char <= 126);
            if ($char === 32 && $num + 1 === $all) {
                $ascii = false;
            }
            if (!$coding && $char === 61 && preg_match('/;(\s+)?([a-z0-9\-]+)(\s+)?(=(\s+)?\"[^\"]+)?/ui', $result)) {
                $ascii = true;
            }
            if ($coding && $symbol === ' ') {
                $ascii = false;
            }
            if ($ascii) {
                if ($coding) {
                    $coding = false;
                    $line = '?=' . $symbol;
                    $add = 3;
                } else {
                    $line = $symbol;
                    $add = 1;
                }
            } else {
                if (!$coding) {
                    $coding = true;
                    $line = $encoder;
                    $add = 10; //TODO per $this->charset
                }
                $line .= $this->map[$char];
                $add += 3;
            }
            if ($position + $add >= $max) {
                if ($coding) {
                    $line = "?={$this->eol} =?utf-8?Q?$line";
                    $position = $add + 11;
                } else {
                    $line = "={$this->eol} $line"; //TODO eol = func(Transport type)
                    $position = $add + 1;
                }
            }
            $result .= $line;
            $position += $add;
        }
        if ($coding) {
            $line = '?=';
            if ($position + 3 >= $max) {
                $line = "={$this->eol} $line";
            }
            $result .= $line;
        }
        return $result;
    }

    /**
     * @param string $pathOrContent Path of file, or actual content
     * @return string
     */
    private function detectMimeType($pathOrContent)
    {
        if (function_exists('finfo_open')) {
            /** @noinspection PhpComposerExtensionStubsInspection */
            $info = finfo_open(FILEINFO_MIME);
        } else {
            $info = null;
        }
        $isFile = file_exists($pathOrContent);
        if ($info) {
            if ($isFile) {
                /** @noinspection PhpComposerExtensionStubsInspection */
                $mime = finfo_file($info, $pathOrContent);
            } else {
                /** @noinspection PhpComposerExtensionStubsInspection */
                $mime = finfo_buffer($info, $pathOrContent);
            }
            /** @noinspection PhpComposerExtensionStubsInspection */
            finfo_close($info);
            return $mime;
        }
        if ($isFile) {
            $arr = explode('.', $pathOrContent);
            if ($arr) {
                $ext = strtolower(end($arr));
                if (isset($this->mime[$ext])) {
                    return $this->mime[$ext];
                }
                include __DIR__ . DIRECTORY_SEPARATOR . 'MIMEtypes.php';
                if (isset($longTypes[$ext])) {
                    if (!is_array($longTypes[$ext])) {
                        return $longTypes[$ext];
                    }
                    return $longTypes[$ext][0];
                }
            }
        }
        return 'application/octet-stream';
    }
}
