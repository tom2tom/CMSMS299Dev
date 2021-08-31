<?php
/*
Class Mailer - a wrapper around an external backend mailer system
Copyright (C) 2014-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

This file is a component of CMS Made Simple module CMSMailer.

This CMSMailer module is free software; you may redistribute it and/or
modify it under the terms of the GNU Affero General Public License as
published by the Free Software Foundation; either version 3 of that license,
or (at your option) any later version.

This CMSMailer module is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.

See the GNU Affero General Public License
<http://www.gnu.org/licenses/licenses.html#AGPL> for more details.
*/
namespace CMSMailer;

use CMSMailer\PrefCrypter;
use CMSMS\AppParams;
use CMSMS\Crypto;
use CMSMS\DeprecationNotice;
use CMSMS\IMailer;
use Ddrv\Mailer\Mailer as DoMailer;
use Ddrv\Mailer\Message;
use Ddrv\Mailer\Transport\FakeTransport;
use Ddrv\Mailer\Transport\FileTransport;
use Ddrv\Mailer\Transport\PHPmailTransport;
use Ddrv\Mailer\Transport\SendmailTransport;
use Ddrv\Mailer\Transport\SmtpTransport;
use Ddrv\Mailer\Transport\SpoolTransport;
use Exception;
use const CMS_DEPREC;
use const TMP_CACHE_LOCATION;
use function CMSMS\de_entitize;
use function cms_join_path;

/**
 * A class for interfacing with Ddrv Mailer to send email.
 *
 * @package CMS
 * @license GPL
 */
class Mailer implements IMailer
{
	/**
	 * @ignore
	 */
	private $message;
	private $transport;
//	private $headers;
	private $from; //local cache in lieu of message API
	private $ishtml;
	private $single;
	private $errmsg;

	/**
	 * Constructor
	 *
	 * @param bool $throw Optionally enable exception when autoloader registration fails.
	 */
	public function __construct($throw = false)
	{
		if (spl_autoload_register([$this, 'MailerAutoload'], $throw)) {
			$this->reset();
		} else {
			$this->message = null;
		}
	}

	/**
	 * @ignore
	 * @param string $method method to call in downstream mailer class
	 * @param array $args Arguments passed to method
	 */
	public function __call($method, $args)
	{
		if (method_exists($this->message, $method)) {
			return call_user_func([$this->message, $method], ...$args);
		}
	}

	public function MailerAutoload($classname)
	{
		$p = strpos($classname, 'Ddrv\Mailer\\');
		if ($p === 0 || ($p == 1 && $classname[0] == '\\')) {
			$parts = explode('\\', $classname);
			if ($p == 1) {
				unset($parts[0]);
			}
			unset($parts[$p], $parts[$p+1]);
			$fp = cms_join_path(__DIR__, 'ddrv-mailer', ...$parts) . '.php';
			if (is_readable($fp)) {
				include_once $fp;
			}
		}
	}

	/**
	 * [Re]set vanilla status
	 */
	public function reset()
	{
		$this->message = new Message();
		$this->transport = [];
//		$this->headers = [];
		$this->from = [];
		$this->ishtml = false;
		$this->errmsg = '';
		$mailprefs = [
			'mailer' => 1,
			'from' => 1,
			'fromuser'  => 1,
			'charset' => 1,
			'host' => 1,
			'port' => 1,
			'sendmail' => 1,
			'smtpauth' => 1,
			'username' => 1,
			'password' => 1,
			'secure' => 1,
			'timeout' => 1,
			//extras for this mailer-backend
//			'batchgap' => 1, // unused ?
//			'batchsize' => 1, // unused ?
			'single' => 1,
		];
		foreach ($mailprefs as $key => &$val) {
			$val = $this->GetPreference($key);
		}
		unset($val);
		$val = AppParams::get('mailprefs');
		if ($val) {
			$mailprefs = array_merge($mailprefs, unserialize($val, ['allowed_classes' => false]));
		}
		$pw = PrefCrypter::decrypt_preference(PrefCrypter::MKEY);
		$mailprefs['password'] = Crypto::decrypt_string(base64_decode($mailprefs['password']), $pw);

		$value = $mailprefs['from'] ?? $mailprefs['fromuser'] ?? '';
		$this->message->setSender(trim($value)); //TODO ($email, $name)
		$this->message->setCharset(trim($mailprefs['charset']));

		switch (strtolower($mailprefs['mailer'])) {
			case 'smtp':
				$this->transport['object'] = new SmtpTransport(
					$mailprefs['host'],
					$mailprefs['port'],
					$mailprefs['username'],
					$mailprefs['password'],
					$mailprefs['from'],
					$mailprefs['secure'],
					$mailprefs['host']
				);
				break;
			case 'sendmail':
				$this->transport['object'] = new SendmailTransport([
					'sendmailapp' => $mailprefs['sendmail'],
					'sender' => $mailprefs['from'],
					'delay-silent' => 1, // no warning re deferred send
					//TODO other sendmail options
				]);
				break;
			case 'file':
				$this->transport['object'] = new FileTransport(TMP_CACHE_LOCATION);
				break;
			case 'spool':
				//TODO
				$this->transport['object'] = new SpoolTransport();
				break;
			case 'test':
				$this->transport['object'] = new FakeTransport();
				break;
			default:
				$options = []; //TODO
				$this->transport['object'] = new PHPmailTransport($options);
				break;
		}

		//TODO some of these relevant only for a specific transport (smtp etc)
		$this->transport['host'] = trim($mailprefs['host']);
		$this->transport['port'] = (int)$mailprefs['port'];
		$this->transport['login'] = trim($mailprefs['username']); // only used when using the SMTP mailer with SMTP authentication ?
		$value = $mailprefs['password'] ?? '';
		$this->transport['password'] = ($value) ? Crypto::decrypt_string(base64_decode($value), $pw) : '';
		$this->transport['encrytion'] = trim($mailprefs['secure']); // 'tls' 'ssl' or ''
		$this->transport['smtpauth'] = (bool)$mailprefs['smtpauth'];
		$this->transport['timeout'] = (int)$mailprefs['timeout'];
		$this->transport['priority'] = 1; // highest

		$this->single = !empty($mailprefs['single']);
	}

	/**
	 * Send message(s), using the API of PHP's mail()
	 * See https://www.php.net/manual/en/function.mail
	 * @since 6.3
	 *
	 * @param string $to one or more destination(s) (comma-separated if multiple)
	 * @param string $subject plaintext
	 * @param string $message plaintext or html
	 * @param mixed $additional_headers optional array | string (CRLF-separated if multiple)
	 * @param string $additional_params optional sendmail-command params
	 * @return bool indicating message was accepted for delivery
	 */
	public function send_simple(string $to, string $subject, string $message,
	    $additional_headers = [], string $additional_params = '') : bool
	{
		$this->reset();
		if (strpos($to, ',') !== false) {
			$arr = explode(',', $to);
			foreach ($arr as $addr) {
				$this->AddAddress(trim($addr));
			}
		} else {
			$this->AddAddress($to);
		}
		$this->SetSubject($subject);
		if (strpos($message, '<') === false) {
			$flag = false;
		} else {
			$flag = ($message != strip_tags($message));
		}
		$this->IsHTML($flag);
		//TODO somewhere enforce CRLF linebreaks in the body, and line-length <= 70 chars, if using sendmail at least
		if ($additional_headers) {
			if (is_array($additional_headers)) {
				foreach ($additional_headers as $headername => $value) {
					//TODO validate
					$this->AddCustomHeader($headername, $value);
				}
			} else {
				$arr = explode("\r\n", $additional_headers);
				foreach ($arr as $val) {
					$parts = explode(':', $val);
					if (isset($parts[0]) && isset($parts[1])) {
						$headername = trim($parts[0]);
						$value = trim($parts[1]);
						//TODO validate
						if ($headername && $value) {
							$this->AddCustomHeader($headername, $value);
						}
					}
				}
			}
		}
		if ($additional_params) {
			if ($this->transport['object'] instanceof SendmailTransport) {
				$this->transport['object']['sendmailapp'] .= ' ' . $additional_params;
			}
		}
		$this->Send();
		$ret = $this->IsError();
		$this->reset();
		return $ret;
	}

	/**
	 * Utility-method, replicates the private cleaner in the Message class
	 * @param string $name
	 * @return string maybe empty
	 */
	public function CleanName(string $name) : string
	{
		if ($name || is_numeric($name)) {
			return preg_replace('/[^\w\pL ,.]/i', '', trim($name));
		}
		return '';
	}

	/**
	 * Utility-method, scrubs HTML tags from the supplied string, after
	 * replacing relevant newlines
	 * @param string $content
	 * @return string
	 */
	public function CleanHtmlBody(string $content) : string
	{
		if ($content) {
			$value = preg_replace(
			['~\<br\s*/?\>~i','~\</p\>~i','~\</h\d\>~i'],
			["\r\n","\r\n\r\n","\r\n\r\n"],
			$content);
			$content = de_entitize(strip_tags($value), ENT_QUOTES);
		}
		return $content;
	}

	public function GetInnerMailer()
	{
		return $this->message;
	}

	/**
	 * Set the subject of the message
	 * @param string $subject
	 */
	public function SetSubject(string $subject)
	{
		$this->message->setSubject($subject);
	}

	/**
	 *
	 * @return string
	 */
	public function GetSubject() : string
	{
		return $this->message->getSubject();
	}

	/**
	 * Set the from address for the email
	 * Optionally set a name for the sender
	 *
	 * @param string $email email address that the email will be from.
	 * @param string $name optional sender's name
	 */
	public function SetFrom(string $email, string $name = '')
	{
		$this->message->setSender($email, $name);
		$this->from[$email] = $name; //local cache enables retrieval
	}

	/**
	 * Get the email sender.
	 * @param string $email
	 * @return string
	 */
	public function GetFrom(string $email = '') : string
	{
		if ($email) {
			return $this->from[$email] ?? '';
		}
		return ($this->from) ? reset($this->from) : '';
	}

	/**
	 * Add a "To" address.
	 * @param string $email The email address
	 * @param string $name  The sender's name
	 * @return bool true on success, false if address already used
	 */
	public function AddAddress(string $email, string $name = '') : bool
	{
		$this->message->addRecipient($email, $name);
		return true;
	}

	/**
	 * Remove a "To" address.
	 * @param string $email
	 */
	public function RemoveAddress(string $email)
	{
		$this->message->removeRecient($email);
	}

	/**
	 * Get all "To" addresses.
	 * @return array
	 */
	public function GetAddresses() : array
	{
		return $this->message->getRecipients();
	}

	/**
	 * Add a "CC" address.
	 * @param string $email The email address
	 * @param string $name  The recipient's name
	 * @return bool true on success, false if address already used
	 */
	public function AddCC(string $email, string $name = '') : bool
	{
		$this->message->addCc($email, $name);
		return true;
	}

	/**
	 * Remove a "CC" address.
	 * @param string $email
	 */
	public function RemoveCC(string $email)
	{
		$this->message->removeCc($email);
	}

	/**
	 * Get all "CC" addresses.
	 * @return array
	 */
	public function GetCC() : array
	{
		return $this->message->getCc();
	}

	/**
	 * Add a "BCC" address.
	 * @param string $email The email address
	 * @param string $name  The real name
	 * @return bool true on success, false if address already used
	 */
	public function AddBCC(string $email, string $name = '') : bool
	{
		$this->message->addBcc($email, $name);
		return true;
	}

	/**
	 * Remove a "BCC" address.
	 * @param string $email
	 */
	public function RemoveBCC(string $email)
	{
		$this->message->removeBcc($email);
	}

	/**
	 * Get all "BCC" addresses.
	 * @return array
	 */
	public function GetBCC() : array
	{
		return $this->message->getBcc();
	}

	/**
	 * Set the (initial) "Reply-to" address.
	 * @param string $email
	 */
	public function SetReplyTo(string $email)
	{
		$this->message->setHeader('Reply-To', $value);
	}

	/**
	 *
	 */
	public function RemoveReplyTo()
	{
		$this->message->removeHeader('Reply-To');
	}

	/**
	 *
	 * @return string
	 */
	public function GetReplyTo() : string
	{
		return $this->message->getHeaderTODO('Reply-To');
	}

	/**
	 *
	 * @param string $email
	 */
	public function SetConfirmTo(string $email)
	{
		$this->message->setHeader('Disposition-Notification-To', $email);
	}

	/**
	 *
	 */
	public function RemoveConfirmTo()
	{
		$this->message->removeHeader('Disposition-Notification-To');
	}

	/**
	 *
	 * @return string
	 */
	public function GetConfirmto() : string
	{
		return $this->message->getHeaderTODO('Disposition-Notification-To');
	}

	/**
	 * Add a custom header to the output email
	 *
	 * e.g. $mailerobj->addCustomHeader('X-MYHEADER', 'some-value');
	 * @param string $headername
	 * @param string $value
	 */
	public function AddCustomHeader(string $headername, string $value)
	{
		$this->message->setHeader($headername, $value);
	}

	/**
	 *
	 * @param string $headername
	 */
	public function RemoveCustomHeader(string $headername)
	{
		$this->message->removeHeader($headername);
	}

	/**
	 * [Un]set the message content type to HTML.
	 * @param bool $state Default true
	 */
	public function IsHTML(bool $state = true)
	{
		$this->ishtml = $state;
	}

	/**
	 * Set the body of the email message.
	 *
	 * If the email message is in HTML format this can contain HTML code.
	 * Otherwise it should contain only text.
	 * @param string $content
	 */
	public function SetBody(string $content)
	{
		if ($this->ishtml) {
			$this->message->setHtml($content);
		} else {
			$content = $this->CleanHtmlBody($content);
			$this->message->setText($content);
		}
	}

	/**
	 * Set the alternate body of the email message
	 *
	 * For HTML messages the alternate body contains a text-only string
	 * for email clients without HTML support.
	 * @param string $content
	 */
	public function SetAltBody(string $content)
	{
		if ($this->ishtml) {
			$content = $this->CleanHtmlBody($content);
			$this->message->setText($content);
		} else {
			$this->message->setHtml($content);
		}
	}

	/**
	 *
	 * @param string $name
	 * @param string $content
	 * @param string $path
	 */
	public function AddAttach(string $name, string $content = '', string $path = '')
	{
		if ($content) {
			$this->message->attachFromString($name, $content);
		} elseif ($path && is_file($path)) {
			if (!$name) {
				$name = basename($path);
			}
			$this->message->attachFromFile($name, $path);
		}
	}

	/**
	 * Report whether the backend mailer supports sending individual
	 * messages as an alternative to one message to multiple destinations
	 * @return bool
	 */
	public function IsSingleAddressor() : bool
	{
		return true;
	}

	/**
	 * [Un]set the flag indicating intent to send an individual message to each destination
	 * @param bool $state Default true
	 */
	public function SetSingleSend(bool $state = true)
	{
//		if ($this->IsSingleAddressor()) {
			$this->single = $state;
//		}
	}

	/**
	 *
	 * @param string $name
	 */
	public function RemoveAttach(string $name)
	{
		$this->message->detach($name);
	}

	/**
	 * Check whether there was an error on the last message send
	 * @return bool
	 */
	public function IsError() : bool
	{
		return $this->errmsg != false;
	}

	/**
	 * Return the error information from the last error.
	 * @return string
	 */
	public function GetErrorInfo() : string
	{
		return $this->errmsg;
	}

	/**
	 * Send the message
	 * @param int $batchsize optional No. of messages to send in a single batch Default 0 hence no limit
	 */
	public function Send(int $batchsize = 0)
	{
		if ($this->single) {
			$this->SendSingles($batchsize);
			return;
		}
/*
		$spool = new FileSpool($this->transport['object'], sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'mail');
		//OR
		$spool = new MemorySpool($this->transport['object']);

		$mailer = new DoMailer($spool);
		//OR
		$mailer = new DoMailer($spool, /*from options* /);

		$message = new Message(
			'Test message',           // subject of message
			'<h1>Hello, world!</h1>', // html body
			'Hello, world!'           // plain body
		);
		//OR
		$html = <<<HTML
<h1>Welcome to Fight Club</h1>
<p>Please, read our rules in attachments</p>
HTML;
		$text = <<<TEXT
Welcome to Fight Club
Please, read our rules in attachments
TEXT;
		$message = new Message();
		$message
			->setSubject('Fight Club')
			->setHtml($html)
			->setText($text);

		$message->addTo('tyler@fight.club', 'Tyler Durden');
		$message->addCc('bob@fight.club', 'Robert Paulson');
		$message->addBcc('angel@fight.club', 'Angel Face');

		$rules = <<<TEXT
1. You don't talk about fight club.
2. You don't talk about fight club.
TEXT;
		$message->attachFromString(
			'fight-club.txt', // attachment name
			$rules,           // content
			'text/plain'      // content-type
		);
		//OR
		$path = '/home/tyler/docs/projects/mayhem/rules.txt';
		$message->attachFromFile(
			'project-mayhem.txt',  // attachment name
			 $path                 // path to attached file
		);
*/
		$mailer = new DoMailer($this->transport['object']);
		try {
			$mailer->send($this->message /*, int priority*/);
/*			//OR
			$mailer->send($this->message, $batchsize);
			$mailer->flush();
			//OR
			$mailer->flush($batchsize);
			//OR
			$mailer->personal($this->message); // without spool
			// OR
			$mailer->personal($this->message, $batchsize); // with spool
			$mailer->flush();
*/
		} catch (Exception $e) {
			$this->errmsg = $e->GetMessage();
		}
	}

	protected function AdjustBody($mode)
	{
		// TODO $mod = ;
		// TODO prepend $mod->Lang('bodyccnotice' | 'bodybccnotice') (with appropriate line-breaks) to body content
	}

	/**
	 * Send the message individually to each specified destination
	 * @param int $batchsize optional No. of messages to send in a single batch Default 0 hence no limit
	 */
	protected function SendSingles(int $batchsize = 0)
	{
		$allto = $this->GetAddresses();
		$allcc = $this->GetCC();
		$allbcc = $this->GetBCC();
/* TODO
		$this->ClearAllRecipients();

		process each $allto member as single destination

		if ($allcc) {
			$body = $this->GetBody();
			$this->AdjustBody('cc');
			process each $allcc member as single destination
			$this->SetBody($body); && Alt
		}

		if ($allbcc) {
			$body = $this->GetBody();
			$this->AdjustBody('bcc');
			process each $allbcc member as single destination
 			$this->SetBody($body); && Alt
		}
*/
		$this->errmsg = 'Not yet supported';
	}

	// ============= OLD-CLASS METHODS =============

	//deprecated alias
	public function AddAttachment($path, $name = '')
	{
		assert(empty(CMS_DEPREC), new DeprecationNotice('method', 'addAttach'));
		$this->AddAttach($name, '', $path);
	}

	/**
	 * Add an embedded attachment.	This can include images, sounds, and
	 * just about any other document.	Make sure to set the $type to an
	 * image type.	For JPEG images use "image/jpeg" and for GIF images
	 * use "image/gif".
	 * @param string $path Path to the attachment.
	 * @param string $cid Content ID of the attachment. Use this to
	 *  identify the Id for accessing the image in an HTML form.
	 * @param string $name Overrides the attachment name.
	 * @param string $encoding File encoding (see $Encoding).
	 * @param string $type File extension (MIME) type.
	 * @return bool
	 */
	function AddEmbeddedImage($path, $cid, $name = '')
	{
		assert(empty(CMS_DEPREC), new DeprecationNotice('method', 'addAttach'));
		$this->AddAttach($name, '', $path);
	}

	//deprecated alias
	public function AddStringAttachment($string, $name)
	{
		assert(empty(CMS_DEPREC), new DeprecationNotice('method', 'addAttach'));
		$this->AddAttach($name, $string, '');
	}

	public function AddReplyTo($email)
	{
		//TODO add comma-separated $email
	}

	public function ClearAddresses()
	{
		//TODO alias for ClearAllRecipients() ??
	}

	public function ClearAllRecipients()
	{
		$this->message->removeRecipients('');
	}

	public function ClearAttachments()
	{
		foreach ($TODO as $name) {
			$this->message->detach($name);
		}
	}

	public function ClearBCCs()
	{
		$this->message->removeRecipients(Message::RECIPIENT_BCC);
	}

	public function ClearCCs()
	{
		$this->message->removeRecipients(Message::RECIPIENT_CC);
	}

	public function ClearCustomHeaders()
	{
	}

	//deprecated alias
	public function ClearReplyTos()
	{
		assert(empty(CMS_DEPREC), new DeprecationNotice('method', 'RemoveReplyTo'));
		$this->RemoveReplyTo();
	}

	public function GetAltBody()
	{
		//TODO return $this->message->X();
		return '';
	}

	public function GetBody()
	{
		//TODO return $this->message->X();
		return '';
	}

	public function GetCharSet()
	{
		return '';
	}

	public function GetConfirmReadingTo()
	{
		return '';
	}

	public function GetEncoding()
	{
		return '';
	}

	public function GetFromName()
	{
		return '';
	}

	public function GetHelo()
	{
		return '';
	}

	public function GetHostname()
	{
		return $this->transport[''];
	}

	public function GetMailer()
	{
		return '';
	}

	public function GetPriority()
	{
		return $this->transport[''];
	}

	public function GetSender()
	{
		return $this->transport[''];
	}

	public function GetSendmail()
	{
		//return $mailprefs['sendmail']
	}

	public function GetSMTPAuth()
	{
		return (bool)$this->transport['smtpauth'];
	}

	public function GetSMTPDebug()
	{
		return $this->transport[''];
	}

	public function GetSMTPHost()
	{
		return $this->transport['host'];
	}

	public function GetSMTPKeepAlive()
	{
		return $this->transport[''];
	}

	public function GetSMTPPassword()
	{
		return $this->transport['password'];
	}

	public function GetSMTPPort()
	{
		return $this->transport['port'];
	}

	public function GetSMTPSecure()
	{
		return $this->transport['encrytion'];
	}

	public function GetSMTPTimeout()
	{
		return $this->transport['timeout'];
	}

	public function GetSMTPUsername()
	{
		return $this->transport['login'];
	}

	public function GetWordWrap()
	{
		return 0; //$this->message[''];
	}

	public function IsMail()
	{
		return $this->transport['object'] instanceof PHPmailTransport;
	}

	public function IsSendmail()
	{
		return $this->transport['object'] instanceof SendmailTransport;
	}

	public function IsSMTP()
	{
		return $this->transport['object'] instanceof SmtpTransport;
	}

	/**
	 * Set the character set for the message.
	 * Normally, the reset routine sets this to a system-wide default value.
	 *
	 * @param string $charset
	 */
	public function SetCharSet($charset)
	{
		$this->message[''] = $charset;
	}

	//deprecated alias
	public function SetConfirmReadingTo($email)
	{
		assert(empty(CMS_DEPREC), new DeprecationNotice('method', 'setConfirmTo'));
		$this->SetConfirmTo($email);
	}

	/**
	 * Sets the encoding of the message (apart from its header).
	 * Supported values are: '7bit', '8bit', 'binary', 'base64', 'quoted-printable'
	 *
	 * @param string $encoding
	 */
	public function SetEncoding($encoding)
	{
		$encoding = strtolower($encoding);
		switch($encoding) {
		case '7bit':
		case '8bit':
		case 'binary':
		case 'base64':
		case 'quoted-printable':
			$this->message->setEncoding = $encoding;
			break;
		default:
			throw new Exception('Invalid message encoding value: '.$encoding);
		}
	}

	public function SetFromName($name)
	{
		$this->message[''] = $name;
	}

	public function SetHelo($helo)
	{
		$this->transport[''] = $helo;
	}

	public function SetHostname($hostname)
	{
		$this->transport['host'] = $hostname;
	}

	public function SetLanguage($lang_type)
	{
		$this->transport[''] = $lang_type;
	}

	public function SetMailer($mailer)
	{
		switch (strtolower($mailer)) {
//			$this->transport[''] = $mailer;
		}
	}

	/**
	 * Set the priority of the message
	 * @param int $priority 1(highest), 2 ....
	 */
	public function SetPriority($priority)
	{
		if (!is_numeric($priority) || $priority < 1) { $priority = 1; }
		$this->transport['priority'] = (int)$priority;
	}

	public function SetSender($sender)
	{
		$this->message[''] = $sender;
	}

	public function SetSendmail($path)
	{
		$this->transport[''] = $path;
	}

	/**
	 * Set a flag indicating whether or not SMTP authentication is to be used
	 * when sending mails via the SMTP mailer.
	 *
	 * @param bool $state
	 * @see Mailer::SetMailer
	 */
	function SetSMTPAuth($state = true)
	{
		$this->transport['smtpauth'] = (bool)$state;
	}

	public function SetSMTPDebug($state = true)
	{
	// does nothing $this->transport[''] = (bool)$state;
	}

	public function SetSMTPHost($host)
	{
		$this->transport['host'] = $host;
	}

	/**
	 * Prevent the SMTP connection from being closed after sending each message.
	 * If this is set to true then SmtpClose() must be called to close
	 * the connection when the session is finished
	 *
	 * This method is only relevant when using the SMTP mailer.
	 *
	 * @param bool $state
	 * @see Mailer::SetMailer
	 * @see Mailer::SmtpClose
	 */
	function SetSMTPKeepAlive($state = true)
	{
// does nothing	$this->transport[''] = $state;
	}

	public function SetSMTPPassword($password)
	{
		$this->transport['password'] = $password;
	}

	public function SetSMTPPort($port)
	{
		$this->transport['port'] = (int)$port;
	}

	public function SetSMTPSecure($type)
	{
		$this->transport['encryption'] = $type;
	}

	public function SetSMTPTimeout($timeout)
	{
		$this->transport['timeout'] = (int)$timeout;
	}

	public function SetSMTPUsername($username)
	{
		$this->transport['login'] = $this->CleanName($username);
	}

	public function SetWordWrap($chars)
	{
//		$this->message[''] = (int)$chars;
	}

	/**
	 * Close the SMTP connection
	 * Only necessary when using the SMTP mailer with keepalive enabled.
	 * @see Mailer::SetSMTPKeepAlive
	 */
	function SmtpClose()
	{
	//does nothing  $this->transport->SmtpClose();
	}
} // class
