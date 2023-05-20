<?php
/*
Class Mailer - a wrapper around an external backend mailer system
Copyright (C) 2014-2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

This file is a component of CMS Made Simple module OutMailer.

This OutMailer module is free software; you may redistribute it and/or
modify it under the terms of the GNU Affero General Public License as
published by the Free Software Foundation; either version 3 of that license,
or (at your option) any later version.

This OutMailer module is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.

See the GNU Affero General Public License
<http://www.gnu.org/licenses/licenses.html#AGPL> for more details.
*/
namespace OutMailer;

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
use OutMailer\IOAuthTokenProvider;
use OutMailer\PrefCrypter;
use const CMS_DEPREC;
use const TMP_CACHE_LOCATION;
use function cms_join_path;
use function CMSMS\de_entitize;
use function get_module_param;
use function lang_by_realm;

/**
 * A class for interfacing with Ddrv Mailer to send email.
 * NOTE: type-declarations here are limited, to conform
 * to the IMailer interface, which is essentially PHPMailer's API
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
	private $single; //enum 0|1|2
	private $errmsg;
	private $oauthProvider; // needed here? IOAuthTokenProvider object
	/**
	 * 'CRAM-MD5', 'LOGIN', 'PLAIN' or 'XOAUTH2'
	 * If not specified, the first one of those that the server supports
	 * will be used.
	 * @var string
	 */
	private $oauthType;
	private $oauthUserEmail; // same as $from?
	private $oauthClientId;
	private $oauthClientSecret;
	private $oauthRefreshToken; //runtime setting

	/**
	 * Constructor
	 *
	 * @param bool $throw Optionally enable exception when autoloader registration fails.
     * But spl_autoload_register() always throws for recent PHP
	 */
	public function __construct($throw = false)
	{
        if ($throw) {
            spl_autoload_register([$this, 'MailerAutoload']);
			$this->reset();
		} else {
            try {
                spl_autoload_register([$this, 'MailerAutoload']);
        		$this->reset();
            } catch (Throwable $t) {
    			$this->message = null; // no object
            }
		}
	}

	/**
	 * @ignore
	 * @param string $method method to call in downstream mailer class
	 * @param array $args Arguments passed to method
	 */
	#[\ReturnTypeWillChange]
	public function __call(string $method, array $args)// : mixed
	{
		if (method_exists($this->message, $method)) {
			return call_user_func([$this->message, $method], ...$args);
		}
		return null;
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
		//TODO if OAuth2 is globally provided
		//TODO if $this->oauthProvider is available
		$p = strpos($classname, 'League\OAuth2\Client\\');
		if ($p === 0 || ($p == 1 && $classname[0] == '\\')) {
			$parts = explode('\\', $classname);
			if ($p == 1) {
				unset($parts[0]);
			}
			unset($parts[$p], $parts[$p+1]);
			$fp = cms_join_path(__DIR__, 'LeagueClient', ...$parts) . '.php';
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
		$this->oauthProvider = null; // needed ?
		$this->oauthType = '';
		$this->oauthUserEmail = '';
		$this->oauthClientId = '';
		$this->oauthClientSecret = '';
		$this->oauthRefreshToken = '';

		$mailprefs = [
			'mailer' => 1,
			'from' => 1,
			'fromuser' => 1,
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
//			'oauthProvider' => null, // aka identifier TODO
			'oauthType' => 1,
//			'oauthUserEmail' => 1, //same as from?
			'oauthClientId' => 1,
			'oauthClientSecret' => 1, //TODO crypted
//			'oauthRefreshToken' => 1, runtime value
			'single' => 1,
		];
//TODO consider support e.g. https://github.com/simonrob/email-oauth2-proxy for OAuth connecting
		foreach ($mailprefs as $key => $val) {
			$mailprefs[$key] = get_module_param('OutMailer', $key);
		}
		$pw = PrefCrypter::decrypt_preference(PrefCrypter::MKEY);
		$mailprefs['password'] = Crypto::decrypt_string(base64_decode($mailprefs['password']), $pw);
		$mailprefs['oauthClientSecret'] = Crypto::decrypt_string(base64_decode($mailprefs['oauthClientSecret']), $pw);

		$this->single = (int)$mailprefs['single'];

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
	}

	/**
	 * Send message(s), using the API of PHP's mail()
	 * See https://www.php.net/manual/en/function.mail
	 * @since 6.3
	 *
	 * @param string $to one or more destination(s) (comma-separated if multiple)
	 *  Any or all of them may be like "addr <name>"
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
        $this->SetBody($message);
        $this->SetAltBody($message);
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
		$ret = !$this->IsError();
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
	 * Utility-method, scrubs some HTML tags from the supplied string,
     * after replacing relevant newlines
	 * @param string $content
	 * @return string
	 */
	public function CleanHtmlBody(string $content) : string
	{
		if ($content) {
			$value = preg_replace(
			['~<br[ /]*>~i','~<p>~i','~</p>~i','~<h\d>~i','~</h\d>~i'],
			["\r\n",'',"\r\n\r\n",'',"\r\n\r\n"],
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
	public function SetSubject(string $subject) : void
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
	public function SetFrom(string $email, string $name = '') : void
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
	 * @param string $email The email address, which may be like "addr <name>"
	 * @param string $name Optional distinct recipient's name Default empty
	 * @return bool true on success, false if address already used
	 */
	public function AddAddress(string $email, string $name = '') : bool
	{
		if (!$name) {
			list($email, $name) = $this->ParseAddress($email);
		}
		$this->message->addRecipient($email, $name);
		return true;
	}

	/**
	 * Remove a "To" address.
	 * @param string $email
	 */
	public function RemoveAddress(string $email)
	{
		$this->message->removeRecipient($email);
	}

	/**
	 * Get all "To" addresses.
	 * @return array each member like email=>['type'='to', 'name'=...]
	 */
	public function GetAddresses() : array
	{
		return $this->message->getRecipientHeaders(Message::RECIPIENT_TO);
	}

	/**
	 * Add a "CC" address.
	 * @param string $email The email address, which may be like "addr <name>"
	 * @param string $name Optional distinct recipient's name Default empty
	 * @return bool true on success, false if address already used
	 */
	public function AddCC(string $email, string $name = '') : bool
	{
		if (!$name) {
			list($email, $name) = $this->ParseAddress($email);
		}
		$this->message->addRecipient($email, $name, Message::RECIPIENT_CC);
		return true;
	}

	/**
	 * Remove a "CC" address.
	 * @param string $email
	 */
	public function RemoveCC(string $email)
	{
		$this->message->removeRecipient($email);
	}

	/**
	 * Get all "CC" addresses.
	 * @return array each member like email=>['type'='cc', 'name'=...]
	 */
	public function GetCC() : array
	{
		return $this->message->getRecipientHeaders(Message::RECIPIENT_CC);
	}

	/**
	 * Add a "BCC" address.
	 * @param string $email The email address, which may be like "addr <name>"
	 * @param string $name Optional distinct recipient's name Default empty
	 * @return bool true on success, false if address already used
	 */
	public function AddBCC(string $email, string $name = '') : bool
	{
		if (!$name) {
			list($email, $name) = $this->ParseAddress($email);
		}
		$this->message->addRecipient($email, $name, Message::RECIPIENT_BCC);
		return true;
	}

	/**
	 * Remove a "BCC" address.
	 * @param string $email
	 */
	public function RemoveBCC(string $email)
	{
		$this->message->removeRecipient($email);
	}

	/**
	 * Get all "BCC" addresses.
	 * @return array each member like email=>['type'='bcc', 'name'=...]
	 */
	public function GetBCC() : array
	{
		return $this->message->getRecipientHeaders(Message::RECIPIENT_BCC);
	}

	/**
	 * Set the (initial) "Reply-to" address.
	 * @param string $email, which may be like "addr <name>
	 */
	public function SetReplyTo(string $email) : void
	{
		$this->message->setHeader('Reply-To', $email);
	}

	/**
	 * Remove the/all "Reply-to" address(es).
	 */
	public function RemoveReplyTo() : void
	{
		$this->message->removeHeader('Reply-To');
	}

	/**
	 *
	 * @return mixed string | null
	 */
	public function GetReplyTo() : ?string
	{
		return $this->message->getHeader('Reply-To');
	}

	/**
	 *
	 * @param string $email
	 */
	public function SetConfirmTo(string $email) : void
	{
		$this->message->setHeader('Disposition-Notification-To', $email);
	}

	/**
	 *
	 */
	public function RemoveConfirmTo() : void
	{
		$this->message->removeHeader('Disposition-Notification-To');
	}

	/**
	 *
	 * @return mixed string | null
	 */
	public function GetConfirmto() : ?string
	{
		return $this->message->getHeader('Disposition-Notification-To');
	}

	/**
	 * Add a custom header to the output email
	 * e.g. $mailerobj->addCustomHeader('X-MYHEADER: some-value');
	 *
	 * @param string $header
	 */
	public function AddCustomHeader(string $header) : void
	{
		list($headername, $value) = explode(':', $header);
		$this->message->setHeader(trim($headername), trim($value));
	}

	/**
	 *
	 * @param string $headername
	 */
	public function RemoveCustomHeader(string $headername) : void
	{
		$this->message->removeHeader($headername);
	}

	/**
	 * [Un]set the message content type to HTML.
	 * @param bool $state Default true
	 */
	public function IsHTML(bool $state = true) : void
	{
		$this->ishtml = (bool)$state;
	}

	/**
	 * Set the body of the email message.
	 *
	 * If the email message is in HTML format this can contain HTML code.
	 * Otherwise it should contain only text.
	 * @param string $content
	 */
	public function SetBody(string $content) : void
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
	public function SetAltBody(string $content) : void
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
	public function AddAttach(string $name, string $content = '', string $path = '') : void
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
	 *
	 * @param string $name
	 */
	public function RemoveAttach(string $name) : void
	{
		$this->message->detach($name);
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
	 * Set the mode for sending individual message
	 * @param int $state enum 0..2 Default 1 (always)
	 */
	public function SetSingleSend(int $state = 1) : void
	{
		$this->single = min(2, max(0, $state));
	}

	/**
	 * Set the IOAuthTokenProvider to be used for oAuth credentialling
	 */
	public function setOAuth(?IOAuthTokenProvider $oauth)
	{
		$this->oauthProvider = $oauth;
	}

	/**
	 * Get the IOAuthTokenProvider used for oAuth credentialling
	 *
	 * @return OAuthTokenProvider
	 */
	public function getOAuth() : ?IOAuthTokenProvider
	{
		return $this->oauthProvider;
	}

	/**
	 * Set the oAuth2 type to be used for credentialling
	 * @param mixed $val string | null the type - supported values are
	 *   CRAM-MD5, LOGIN, PLAIN, XOAUTH2
	 * @throws Exception if an unsupported type is specified
	 */
	public function setOAuthType(?string $val)
	{
		if ($val) {
			$val = strtoupper($val);
			if (!in_array($val, ['CRAM-MD5', 'LOGIN', 'PLAIN', 'XOAUTH2'])) {
				throw new Exception('Invalid oAuth2 type: '.$val);
			}
		} else {
			$val = '';
		}
		$this->oauthType = $val;
	}

	/**
	 * Get the type used for oAuth credentialling
	 *
	 * @return string | null
	 */
	public function getOAuthType() : ?string
	{
		return $this->oauthType;
	}

	/**
	 * @param string $email
	 */
	public function SetOauthSender(string $email)
	{
		//TODO same as ->from ?
		$this->oauthUserEmail = $email; //TODO cleanup
	}

	/**
	 *
	 * @return string
	 */
	public function GetOauthSender() : string
	{
		return $this->oauthUserEmail;
	}

	/**
	 *
	 * @param string $val
	 */
	public function SetOauthClient(string $val)
	{
		$this->oauthClientId = $val; //TODO cleanup
	}

	/**
	 *
	 * @return string
	 */
	public function GetOauthClient() : string
	{
		return $this->oauthClientId;
	}

	/**
	 *
	 * @param string $val
	 */
	public function SetOauthSecret(string $val)
	{
		$this->oauthClientSecret = $val; //TODO cleanup
	}

	/**
	 *
	 * @return string
	 */
	public function GetOauthSecret() : string
	{
		return $this->oauthClientSecret;
	}
	//$mail->oauthRefreshToken = $accessToken; dynamic

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
	public function Send() // TODO IMailer has no param
	{
		$mailer = new DoMailer($this->transport['object']);
		switch ($this->single) {
			case 1: // always
			case 2: // if no copies
				if ($this->transport['object'] instanceof SmtpTransport) {
					// TODO keep smtp connection alive
				}
				$allto = $this->GetAddresses();
				$allcc = $this->GetCC();
				$allbcc = $this->GetBCC();
				$this->ClearAllRecipients();
				if ($this->single == 1 || $allcc || $allbcc) {
					foreach ($allto as $addr => $props) {
						$this->AddAddress($addr, $props['name']);
						try {
							$mailer->send($this->message /*, int priority*/);
						} catch (Exception $e) {
							$this->errmsg = $e->GetMessage();
						}
						$this->RemoveAddress($addr);
					}
				} else {
					foreach ($allto as $addr => $props) {
						$this->AddAddress($addr, $props['name']);
					}
					try {
						$mailer->send($this->message /*, int priority*/);
					} catch (Exception $e) {
						$this->errmsg = $e->GetMessage();
					}
					$this->ClearAllRecipients();
				}

				if ($allcc) {
					$body = $this->GetBody(); // preserve
					$this->AdjustBody('cc');
 					foreach ($allcc as $addr => $props) {
						$this->AddAddress($addr, $props['name']);
						try {
							$mailer->send($this->message /*, int priority*/);
						} catch (Exception $e) {
							$this->errmsg = $e->GetMessage();
						}
						$this->RemoveAddress($addr);
					}
					$this->SetBody($body); // reinstate
				}

				if ($allbcc) {
					$body = $this->GetBody(); // preserve
					$this->AdjustBody('bcc');
					foreach ($allbcc as $addr => $props) {
						$this->AddAddress($addr, $props['name']);
						try {
							$mailer->send($this->message /*, int priority*/);
						} catch (Exception $e) {
							$this->errmsg = $e->GetMessage();
						}
						$this->RemoveAddress($addr);
					}
					$this->SetBody($body); // reinstate
				}
				if ($this->transport['object'] instanceof SmtpTransport) {
					// TODO quit smtp connection if still open
				}
//				$this->errmsg = 'Not yet supported';
				break;
			default: // never
				try {
					//TODO support authentication using oAuth parameters when relevant
					//provider, type etc etc
					$mailer->send($this->message /*, int priority*/);
/* OR
				$mailer->send($this->message, $batchsize);
				$mailer->flush();
OR
				$mailer->flush($batchsize);
OR
				$mailer->personal($this->message); // without spool
OR
				$mailer->personal($this->message, $batchsize); // with spool
				$mailer->flush();
*/
				} catch (Exception $e) {
					$this->errmsg = $e->GetMessage();
				}
				break;
		}
	}
/*
Microsoft and get the necessary credentials (client ID and client secret)

use League\OAuth2\Client\Provider\Microsoft;

$provider = new Microsoft([
    'clientId' => 'your-client-id',
    'clientSecret' => 'your-client-secret',
    'redirectUri' => 'http://your-redirect-uri/'
]);

if (!isset($_GET['code'])) {
    // If the user has not authorized your app, redirect them to the Microsoft login page
    $authUrl = $provider->getAuthorizationUrl();
    header('Location: '.$authUrl);
    exit;

} else {
    // If the user has authorized your app, get an access token
    $accessToken = $provider->getAccessToken('authorization_code', [
        'code' => $_GET['code']
    ]);
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

$mail = new PHPMailer();

// Set the mailer to use SMTP
$mail->isSMTP();

// Set the SMTP server
$mail->Host = 'smtp.office365.com';

// Enable SMTP authentication
$mail->SMTPAuth = true;

// Set the SMTP authentication type (Microsoft OAuth 2.0)
$mail->AuthType = 'XOAUTH2';

// Set the OAuth 2.0 access token
$mail->oauthUserEmail = 'your-email@example.com';
$mail->oauthClientId = 'your-client-id';
$mail->oauthClientSecret = 'your-client-secret';
$mail->oauthRefreshToken = $accessToken;

// Set the email parameters
$mail->setFrom('your-email@example.com', 'Your Name');
$mail->addAddress('recipient@example.com', 'Recipient Name');
$mail->Subject = 'Email subject';
$mail->Body = 'Email body';

// Send the email
$mail->send();
*/
/*		$spool = new FileSpool($this->transport['object'], sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'mail');
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
			'project-mayhem.txt', // attachment name
			 $path                // path to attached file
		);
*/
	protected function AdjustBody(string $mode) : void
	{
		switch ($mode) {
			case 'cc':
				$str = lang_by_realm('OutMailer', 'bodyccnotice');
				break;
			case 'bcc':
				$str = lang_by_realm('OutMailer', 'bodybccnotice');
				break;
			default:
				return;
		}
		$pref = ($this->ishtml) ? nl2br($str) : $str;
		$sep = ($this->ishtml) ? '<br>' : "\r\n"; // TODO generalise e.g. $this->transport->$eol
		$body = $this->GetBody();
		$this->SetBody($pref . $sep . $sep . $body);
	}

	/**
	 * Parse supplied $email in case it's like "addr <name>"
	 * But "<name> addr" is not supported
	 * @param string $email
	 * @return 2-member array [0] = email, [1] = name or ''
	 */
	protected function ParseAddress(string $email) : array
	{
		$s = trim($email);
		if (($p = strpos($s, '<')) !== false) {
			if (($q = strpos($s, '>', $p+1)) !== false) {
				$email = trim(substr($s, $p+1, $q-$p-1));
				$name = rtrim(substr($s, 0, $p));
				return [$email, $name];
			}
		}
		return [$s, ''];
	}

	// ============= OLD-CLASS METHODS =============

	//deprecated alias
	public function AddAttachment($path, $name = '', $encoding = 'base64', $type = 'application/octet-stream')
	{
		assert(empty(CMS_DEPREC), new DeprecationNotice('method', 'addAttach'));
		$this->AddAttach($name, '', $path);
	}

	/**
	 * Add an embedded attachment.	This can include images, sounds, and
	 * just about any other document.	Make sure to set the $type to an
	 * image type. For JPEG images use "image/jpeg" and for GIF images
	 * use "image/gif".
	 * @param string $path Path to the attachment.
	 * @param string $cid Content ID of the attachment. Use this to
	 *  identify the Id for accessing the image in an HTML form.
	 * @param string $name Overrides the attachment name.
	 * @param string $encoding File encoding (see $Encoding). UNUSED
	 * @param string $type File extension (MIME) type. UNUSED
	 * @return bool
	 */
	public function AddEmbeddedImage($path, $cid, $name = '', $encoding = 'base64', $type = 'application/octet-stream')
	{
		assert(empty(CMS_DEPREC), new DeprecationNotice('method', 'addAttach'));
		$this->AddAttach($name, '', $path);
	}

	//deprecated alias
	public function AddStringAttachment($string, $name, $encoding = 'base64', $type = 'application/octet-stream')
	{
		assert(empty(CMS_DEPREC), new DeprecationNotice('method', 'addAttach'));
		$this->AddAttach($name, $string, '');
	}

	//deprecated alias
	public function AddReplyTo($email, $name = '')
	{
		assert(empty(CMS_DEPREC), new DeprecationNotice('method', 'SetReplyTo'));
		$s = trim($name);
		if ($s !== '') {
			$s = trim($email).' <'.$s.'>';
		}
		$this->SetReplyTo($s);
	}

	//deprecated alias
	public function ClearAddresses()
	{
		assert(empty(CMS_DEPREC), new DeprecationNotice('method', 'ClearAllRecipients'));
		$this->message->removeRecipients('');
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
		return $this->message->getBodyRaw();
		return $val;
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
	public function SetConfirmReadingTo(?string $email)
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

	public function SetFromName(?string $name)
	{
		$this->message[''] = $name;
	}

	public function SetHelo(?string $helo)
	{
		$this->transport[''] = $helo;
	}

	public function SetHostname(?string $hostname)
	{
		$this->transport['host'] = $hostname;
	}

	public function SetLanguage($lang_type)
	{
		$this->transport[''] = $lang_type;
	}

	public function SetMailer(string $mailer)
	{
		switch (strtolower($mailer)) {
//			$this->transport[''] = $mailer;
		}
	}

	/**
	 * Set the priority of the message
	 * @param int $priority 1(highest), 2 ....
	 */
	public function SetPriority(int $priority)
	{
		if (!is_numeric($priority) || $priority < 1) { $priority = 1; }
		$this->transport['priority'] = (int)$priority;
	}

	public function SetSender(?string $sender)
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
	public function SetSMTPAuth($state = true)
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
	public function SetSMTPKeepAlive($state = true)
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
	public function SmtpClose()
	{
	//does nothing  $this->transport->SmtpClose();
	}
} // class
