<?php
# class Mailer - a simple wrapper around an external mailer system
# Copyright (C) 2004-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
# Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.
# This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.	See the
# GNU General Public License for more details.
# You should have received a copy of the GNU General Public License
# along with this program. If not, see <https://www.gnu.org/licenses/>.

namespace CMSMailer;

use CMSMS\AppParams;
use CMSMS\Crypto;
use CMSMS\Utils;
use Ddrv\Mailer\Transport\PHPmailTransport;
use Ddrv\Mailer\Transport\SendmailTransport;
use Ddrv\Mailer\Transport\SmtpTransport;
//use Ddrv\Mailer\Transport\FakeTransport;
use Ddrv\Mailer\Spool\MemorySpool;
use Ddrv\Mailer\Spool\FileSpool;
use Ddrv\Mailer\Mailer As DoMailer;
use Ddrv\Mailer\Message;

/**
 * A class for sending email.
 *
 * @package CMS
 * @license GPL
 * @since 2.0
 * @author Robert Campbell (calguy1000@cmsmadesimple.org)
 */

class Mailer
{
	/**
	 * @ignore
	 */
	private $message;
	private $transport;
	private $headers;

	/**
	 * Constructor
	 *
	 * @param bool $exceptions Optionally disable autoloader exceptions,
	 *  and rely on error strings.
	 */
	public function __construct($exceptions = true)
	{
		if (spl_autoload_register([$this, 'MailerAutoload'], $exceptions)) {
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
		$p = strpos($classname, 'Ddrv\\Mailer\\');
		if ($p === 0 || ($p == 1 && $classname[0] == '\\')) {
			$parts = explode('\\', $classname);
			if ($p == 1) {
				unset($parts[0]);
			}
			unset($parts[$p], $parts[$p+1]);
			$class = array_pop($parts);
			$fp = cms_join_path(__DIR__, 'php-mailer', implode(DIRECTORY_SEPARATOR, $parts), $class . '.php');
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
		$mod = Utils::get_module('CMSMailer');
		$mailprefs = $mod->GetPreference();

		$this->message = new Message();
		$this->transport = [];
		$this->headers = [];

		$val = $mailprefs['timeout'] ?? 60;
		$this->transport['timeout'] = (int)$val;
		$this->transport['host'] = $mailprefs['host'] ?? '';
		$val = $mailprefs['port'] ?? 25;
		$this->transport['port'] = (int)$val;
		$this->transport['login'] = $mailprefs['username'] ?? ''; // Only used when using the SMTP mailer with SMTP authentication ?
		$val = $mailprefs['password'] ?? '';
		$this->transport['password'] = ($val) ? Crypto::decrypt_string(base64_decode($val)) : '';
		$this->transport['encrytion'] = $mailprefs['secure'] ?? ''; // 'tls' 'ssl' or ''
		$this->transport['smtpauth'] = $mailprefs['smtpauth'] ?? true; // whether smtp must use credentials
		$this->message->setFrom($mailprefs['from'] ?? '', $mailprefs['fromuser'] ?? '');
		$this->headers['charset'] = $mailprefs['charset'] ?? '';

		switch (strtolower($mailprefs['mailer'])) {
			case 'smtp':
				$this->transport['object'] = new SmtpTransport(
				//options
				);
				break;
			case 'sendmail':
				$this->transport['object'] = new SendmailTransport(
				//options
				);
				//TODO other sendmail options
				//$this->transport['sendmailapp'] = $mailprefs['sendmail'];
				break;
			default:
				$this->transport['object'] = new PHPmailTransport(
				//options
				);
				break;
		}
	}

	public function getInnerMailer()
	{
		return $this->message;
	}

	/**
	 * Set the subject of the message
	 * @param string $subject
	 */
	public function setSubject(string $subject)
	{
		$this->message->setSubject($subject);
	}

	/**
	 */
	public function getSubject() : string
	{
		return $this->message->getSubject();
	}

	/**
	 * Set the from address for the email
	 * Optionally set a name for the sender
	 *
	 * @param string $email email address that the email will be from.
	 * @param string $name
	 */
	public function setFrom(string $email, string $name = '')
	{
		$this->message->setFrom($email, $name);
	}

	/**
	 */
	public function getFrom() : array
	{
		return $this->message->TODO();
	}

	/**
	 * Add a "To" address.
	 * @param string $address The email address
	 * @param string $name		The real name
	 * @return bool true on success, false if address already used
	 */
	public function addAddress(string $email, string $name = '') : bool
	{
		return $this->message->addTo($email, $name);
	}

	/**
	 */
	public function removeAddress(string $email)
	{
		$this->message->removeTo($email);
	}

	/**
	 */
	public function getAddresses() : array
	{
		return $this->message->getTo();
	}

	/**
	 * Add a "CC" address.
	 * @param string $address The email address
	 * @param string $name		The real name
	 * @return bool true on success, false if address already used
	 */
	public function addCC(string $email, string $name = '') : bool
	{
		return $this->message->addCc();
	}

	/**
	 */
	public function removeCC(string $email)
	{
		$this->message->removeCc();
	}

	/**
	 */
	public function getCC() : array
	{
		return $this->message->getCc();
	}

	/**
	 * Add a "BCC" address.
	 * @param string $address The email address
	 * @param string $name		The real name
	 * @return bool true on success, false if address already used
	 */
	public function addBCC(string $email, string $name = '') : bool
	{
		return $this->message->addBcc();
	}

	/**
	 */
	public function removeBCC(string $email)
	{
		$this->message->removeBcc();
	}

	/**
	 */
	public function getBCC() : array
	{
		return $this->message->getBcc();
	}

	/**
	 * Set the "Reply-to" address.
	 * @param string $addr
	 * @param string $name
	 * @return bool
	 */
	public function setReplyTo(string $email, string $name = '') : bool
	{
		return $this->message->TODO($email, $name);
	}

	/**
	 */
	public function removeReplyTo(string $email)
	{
		$this->message->TODO($email);
	}

	/**
	 */
	public function getReplyTo() : string
	{
		return $this->message->TODO();
	}

	/**
	 * Add a custom header to the output email
	 *
	 * e.g. $mailerobj->addCustomHeader('X-MYHEADER', 'some-value');
	 * @param string $headername
	 * @param string $body
	 */
	public function addCustomHeader(string $headername, string $body)
	{
		$this->message->setHeader($headername, $body);
	}

	/**
	 */
	public function removeCustomHeader(string $headername)
	{
		$this->message->removeHeader($header);
	}

	/**
	 * [Un]set the message content type to HTML.
	 * @param bool $tsate Default true
	 */
	public function isHTML(bool $state = true)
	{
		return $this->someflag = $state;
	}

	/**
	 * Set the body of the email message.
	 *
	 * If the email message is in HTML format this can contain HTML code.
	 * Otherwise it should contain only text.
	 * @param string $content
	 */
	public function setBody(string $content)
	{
		if ($this->someflag)
		$this->message->setPlainBody($content);
		else
		$this->message->setHtmlBody($content);
	}

	/**
	 * Set the alternate body of the email message
	 *
	 * For HTML messages the alternate body contains a text-only string
	 * for email clients without HTML support.
	 * @param string $content
	 */
	public function setAltBody(string $content)
	{
		if ($this->someflag)
		$this->message->setHtmlBody($content);
		else
		$this->message->setPlainBody($content);
	}

	/**
	 */
	public function addAttach()
	{
		//attachFromString
		//attachFromFile
		$this->message->TODO();
	}

	/**
	 */
	public function removeAttach()
	{
		$this->message->TODO();
	}

	/**
	 * Check whether there was an error on the last message send
	 * @return bool
	 */
	public function isError() : bool
	{
		return $this->message->TODO();
	}

	/**
	 * Return the error information from the last error.
	 * @return string
	 */
	public function getErrorInfo() : string
	{
		return $this->message->TODO();
	}

	/**
	 * Send the message
	 */
	public function Send()
	{
/*
		$spool = new FileSpool($this->transport['object'], sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'mail');
		//OR
		$spool = new MemorySpool($this->transport['object']);

		$mailer = new Mailer($spool);
		//OR
		$mailer = new Mailer($spool, /*from options* /);

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
			->setHtmlBody($html)
			->setPlainBody($text);

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

		$mailer->send($message);
		//OR
		$mailer->send($message, NUM);
		$mailer->flush();
		//OR
		$mailer->flush(LIMIT);
		//OR
		$mailer->personal($message); // without spool
		// or
		$mailer->personal($message, NUM); // with spool
		$mailer->flush();
*/
	}
} // class


/**
 * Set the character set for the message.
 * Normally, the reset routine sets this to a system wide default value.
 *
 * @param string $charset
 */
function SetCharSet($charset)
{
//	$this->message->CharSet = $charset;
}

/**
 * Set the email address that confirmations of email reading will be sent to.
 *
 * @param string $email
 */
function SetConfirmReadingTo($email)
{
//	$this->message->ConfirmReadingTo = $email;
}

/**
 * Sets the encoding of the message.
 *
 * Possible values are: 8bit, 7bit, binary, base64, and quoted-printable
 * @param string $encoding
 */
function SetEncoding($encoding)
{
//	$this->message->setEncoding($encoding);
/*	switch(strtolower($encoding)) {
	case '8bit':
	case '7bit':
	case 'binary':
	case 'base64':
	case 'quoted-printable':
		$this->message->Encoding = $encoding;
		break;
	default:
		// throw exception
	}
*/
}

/**
 * Set a flag indicating whether or not SMTP authentication is to be used when sending
 * mails via the SMTP mailer.
 *
 * @param bool $flag
 * @see Mailer::SetMailer
 */
function SetSMTPAuth($flag = true)
{
	$this->message->SMTPAuth = $flag;
}

/**
 * Add an attachment from a path on the filesystem
 * @param string $path Complete file specification to the attachment
 * @param string $name Set the attachment name
 * @param string $encoding File encoding (see $encoding)
 * @param string $type (mime type for the attachment)
 * @return bool true on success, false on failure.
 */
function AddAttachment($path, $name = '', $encoding = 'base64', $type = 'application/octet-stream')
{
	return $this->message->AddAttachment($path, $name, $encoding, $type);
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
function AddEmbeddedImage($path, $cid, $name = '', $encoding = 'base64', $type = 'application/octet-stream')
{
	return $this->message->AddEmbeddedImage($path, $cid, $name, $encoding, $type);
}

/**
 * Add a string or binary attachment (non-filesystem) to the list.
 * This method can be used to attach ASCII or binary data,
 * such as a BLOB record from a database.
 * @param string $string String attachment data.
 * @param string $filename Name of the attachment.
 * @param string $encoding File encoding (see $Encoding).
 * @param string $type File extension (MIME) type.
 */
function AddStringAttachment($string, $filename, $encoding = 'base64', $type = 'application/octet-stream')
{
	$this->message->AddStringAttachment($string, $filename, $encoding, $type);
}

/**
 * Prevent the SMTP connection from being closed after sending each message.
 * If this is set to true then SmtpClose() must be called to close
 * the connection when the session is finished
 *
 * This method is only useful when using the SMTP mailer.
 *
 * @param bool $flag
 * @see Mailer::SetMailer
 * @see Mailer::SmtpClose
 */
function SetSMTPKeepAlive($flag = true)
{
	$this->message->SMTPKeepAlive = $flag;
}

/**
 * Close the SMTP connection
 * Only necessary when using the SMTP mailer with keepalive enabled.
 * @see Mailer::SetSMTPKeepAlive
 */
function SmtpClose()
{
	return $this->message->SmtpClose();
}
