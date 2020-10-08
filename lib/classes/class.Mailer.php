<?php
# class Mailer - a simple wrapper around PHPMailer
# Copyright (C) 2016-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# You should have received a copy of the GNU General Public License
# along with this program. If not, see <https://www.gnu.org/licenses/>.

namespace CMSMS;

use CMSMS\AppParams;
use CMSMS\DeprecationNotice;
use PHPMailer\PHPMailer\PHPMailer;
use const CMS_DEPREC;

/**
 * A class for interfacing with PHPMailer to send email.
 *
 * Prior to CMSMS 2.0 this class was implemented as a module.
 * @package CMS
 * @license GPL
 * @since 2.0
 * @deprecated since 2.9 due to PHPMailer's incompatible license. Instead
 *  use e.g. CMSMailer\Mailer in the un-deprecated CMSMailer module.
 * @author Robert Campbell (calguy1000@cmsmadesimple.org)
 */
class Mailer
{
  /**
   * @ignore
   */
  private $_mailer;

  /**
   * Constructor
   *
   * @param bool $exceptions Optionally disable exceptions and rely on
   *  error strings.
   */
  public function __construct($exceptions = true)
  {
    assert(empty(CMS_DEPREC), new DeprecationNotice('class', 'e.g. CMSMailer\\Mailer'));
    $this->_mailer = new PHPMailer($exceptions);
    $this->reset();
  }

  /**
   * __call
   *
   * @ignore
   * @param string $method Call method to call from PHP Mailer
   * @param array $args Arguments passed to PHP Mailer method
   */
  public function __call($method,$args)
  {
    if (method_exists($this->_mailer, $method)) {
      return call_user_func_array([$this->_mailer,$method], $args);
    }
  }

  /**
   * Reset the mailer to standard settings
   */
  public function reset()
  {
    $val = AppParams::get('mailprefs');
    $prefs = ($val) ? unserialize($val) : null;
    if (!$prefs) {
      $prefs = [
       'mailer'=>'mail',
       'host'=>'localhost',
       'port'=>25,
       'from'=>'root@localhost.localdomain',
       'fromuser'=>'CMS Administrator',
       'sendmail'=>'/usr/sbin/sendmail',
       'smtpauth'=>0,
       'username'=>'',
       'password'=>'',
       'secure'=>'',
       'timeout'=>60,
       'charset'=>'utf-8',
      ];
    }
    $this->_mailer->Mailer = $prefs['mailer'] ?? 'mail';
    $this->_mailer->Sendmail = $prefs['sendmail'] ?? '/usr/sbin/sendmail';
    $this->_mailer->Timeout = $prefs['timeout'] ?? 60;
    $this->_mailer->Port = $prefs['port'] ?? 25;
    $this->_mailer->FromName = $prefs['fromuser'] ?? '';
    $this->_mailer->From = $prefs['from'] ?? '';
    $this->_mailer->Host = $prefs['host'] ?? '';
    $this->_mailer->SMTPAuth = $prefs['smtpauth'] ?? 0;
    $this->_mailer->Username = $prefs['username'] ?? '';
    $this->_mailer->Password = $prefs['password'] ?? '';
    $this->_mailer->SMTPSecure = $prefs['secure'] ?? '';
    $this->_mailer->CharSet = $prefs['charset'] ?? 'utf-8';
    $this->_mailer->ErrorInfo = '';
    $this->_mailer->ClearAllRecipients();
    $this->_mailer->ClearAttachments();
    $this->_mailer->ClearCustomHeaders();
    $this->_mailer->ClearReplyTos();
  }

  /**
   * Retrieve the alternate body of the email message
   * @return string
   */
  public function GetAltBody()
  {
    return $this->_mailer->AltBody;
  }

  /**
   * Set the alternate body of the email message
   *
   * For HTML messages the alternate body contains a text only string for email clients without HTML support.
   * @param string $txt
   */
  public function SetAltBody($txt)
  {
    $this->_mailer->AltBody = $txt;
  }

  /**
   * Retrieve the body of the email message
   *
   * @return string
   */
  public function GetBody()
  {
    return $this->_mailer->Body;
  }

  /**
   * Set the body of the email message.
   *
   * If the email message is in HTML format this can contain HTML code.  Otherwise it should contain only text.
   * @param string $txt
   */
  public function SetBody($txt)
  {
    $this->_mailer->Body = $txt;
  }

  /**
   * Return the character set for the email
   * @return string
   */
  public function GetCharSet()
  {
    return $this->_mailer->CharSet;
  }

  /**
   * Set the character set for the message.
   * Normally, the reset routine sets this to a system wide default value.
   *
   * @param string $charset
   */
  public function SetCharSet($charset)
  {
    $this->_mailer->CharSet = $charset;
  }

  /**
   * Retrieve the reading confirmation email address
   *
   * @return string The email address (if any) that will recieve the reading confirmation.
   */
  public function GetConfirmReadingTo()
  {
    return $this->_mailer->ConfirmReadingTo;
  }

  /**
   * Set the email address that confirmations of email reading will be sent to.
   *
   * @param string $email
   */
  public function SetConfirmReadingTo($email)
  {
    $this->_mailer->ConfirmReadingTo = $email;
  }

  /**
   * Get the encoding of the message.
   * @return string
   */
  public function GetEncoding()
  {
    return $this->_mailer->Encoding;
  }

  /**
   * Set the encoding of the message.
   *
   * Possible values are: 8bit, 7bit, binary, base64, and quoted-printable
   * @param string $encoding
   */
  public function SetEncoding($encoding)
  {
    switch(strtolower($encoding)) {
    case '8bit':
    case '7bit':
    case 'binary':
    case 'base64':
    case 'quoted-printable':
      $this->_mailer->Encoding = $encoding;
      break;
    default:
      // throw exception
    }
  }

  /**
   * Return the error information from the last error.
   * @return string
   */
  public function GetErrorInfo()
  {
    return $this->_mailer->ErrorInfo;
  }

  /**
   * Get the from address for the email
   *
   * @return string
   */
  public function GetFrom()
  {
    return $this->_mailer->From;
  }

  /**
   * Set the from address for the email
   *
   * @param string $email Th email address that the email will be from.
   */
  public function SetFrom($email)
  {
    $this->_mailer->From = $email;
  }

  /**
   * Get the real name that the email will be sent from
   * @return string
   */
  public function GetFromName()
  {
    return $this->_mailer->FromName;
  }

  /**
   * Set the real name that this email will be sent from.
   *
   * @param string $name
   */
  public function SetFromName($name)
  {
    $this->_mailer->FromName = $name;
  }

  /**
   * Get the SMTP HELO of the message
   * @return string
   */
  public function GetHelo()
  {
    return $this->_mailer->Helo;
  }

  /**
   * Set the SMTP HELO of the message (Default is $Hostname)
   * @param string $helo
   */
  public function SetHelo($helo)
  {
    $this->_mailer->Helo = $helo;
  }

  /**
   * Get the SMTP host values
   *
   * @return string
   */
  public function GetSMTPHost()
  {
    return $this->_mailer->Host;
  }

  /**
   * Set the SMTP host(s).
   *
   * Only applicable when using SMTP mailer.  All hosts must be separated with a semicolon.
   * you can also specify a different port for each host by using the format hostname:port
   * (e.g. "smtp1.example.com:25;smtp2.example.com").
   * Hosts will be tried in order
   * @param string $host
   */
  public function SetSMTPHost($host)
  {
    $this->_mailer->Host = $host;
  }

  /**
   * Get the hostname that will be used in the Message-Id and Recieved headers
   * and the default HELO string.
   * @return string
   */
  public function GetHostname()
  {
    return $this->_mailer->Hostname;
  }

  /**
   * Set the hostname to use in the Message-Id and Received headers
   * and as the default HELO string.  If empty the value will be calculated
   * @param string $hostname
   */
  public function SetHostname($hostname)
  {
    $this->_mailer->Hostname = $hostname;
  }

  /**
   * Retrieve the name of the mailer that will be used to send the message.
   * @return string
   */
  public function GetMailer()
  {
    return $this->_mailer->Mailer;
  }

  /**
   * Set the name of the mailer that will be used to send the message.
   *
   * possible values for this field are 'mail','smtp', and 'sendmail'
   * @param string $mailer
   */
  public function SetMailer($mailer)
  {
    $this->_mailer->Mailer = $mailer;
  }

  /**
   * Get the SMTP password
   * @return string
   */
  public function GetSMTPPassword()
  {
    return $this->_mailer->Password;
  }

  /**
   * Set the SMTP password
   *
   * Only useful when using the SMTP mailer.
   *
   * @param string $password
   */
  public function SetSMTPPassword($password)
  {
    $this->_mailer->Password = $password;
  }

  /**
   * Get the default SMTP port number
   * @return int
   */
  public function GetSMTPPort()
  {
    return $this->_mailer->Port;
  }

  /**
   * Set the default SMTP port
   *
   * This method is only useful when using the SMTP mailer.
   *
   * @param int $port
   */
  public function SetSMTPPort($port)
  {
    $port = max(1,(int) $port);
    $this->_mailer->Port = $port;
  }

  /**
   * Get the priority of the message
   * @return int
   */
  public function GetPriority()
  {
    return (int) $this->_mailer->Priority;
  }

  /**
   * Set the priority of the message
   * (1 = High, 3 = Normal, 5 = low)
   * @param int $priority
   */
  public function SetPriority($priority)
  {
    $priority = max(1,min(5,$priority));
    $this->_mailer->Priority = $priority;
  }

  /**
   * Get the Sender (return-path) of the message.
   * @return string The email address for the Sender field
   */
  public function GetSender()
  {
    return $this->_mailer->Sender;
  }

  /**
   * Set the Sender email (return-path) of the message.
   * @param string $sender
   */
  public function SetSender($sender)
  {
    $this->_mailer->Sender = $sender;
  }

  /**
   * Get the path to the sendmail executable
   * @param string
   */
  public function GetSendmail()
  {
    return $this->_mailer->Sendmail;
  }

  /**
   * Set the path to the sendmail executable
   *
   * This path is only useful when using the sendmail mailer.
   * @param string $path
   * @see Mailer::SetMailer
   */
  public function SetSendmail($path)
  {
    $this->_mailer->Sendmail = $path;
  }

  /**
   * Retrieve the SMTP Auth flag
   * @return bool
   */
  public function GetSMTPAuth()
  {
    return $this->_mailer->SMTPAuth;
  }

  /**
   * Set a flag indicating whether or not SMTP authentication is to be used when sending
   * mails via the SMTP mailer.
   *
   * @param bool $flag
   * @see Mailer::SetMailer
   */
  public function SetSMTPAuth($flag = true)
  {
    $this->_mailer->SMTPAuth = $flag;
  }

  /**
   * Get the current value of the SMTP Debug flag
   * @return bool
   */
  public function GetSMTPDebug()
  {
    return $this->_mailer->SMTPDebug;
  }

  /**
   * Enable, or disable SMTP debugging
   *
   * This is only useful when using the SMTP mailer.
   *
   * @param bool $flag
   * @see Mailer::SetMailer
   */
  public function SetSMTPDebug($flag = TRUE)
  {
    $this->_mailer->SMTPDebug = $flag;
  }

  /**
   * Return the value of the SMTP keepalive flag
   * @return bool
   */
  public function GetSMTPKeepAlive()
  {
    return $this->_mailer->SMTPKeepAlive;
  }

  /**
   * Prevent the SMTP connection from being closed after sending each message.
   * If this is set to true then SmtpClose must be used to close the connection
   *
   * This method is only useful when using the SMTP mailer.
   *
   * @param bool $flag
   * @see Mailer::SetMailer
   * @see Mailer::SmtpClose
   */
  public function SetSMTPKeepAlive($flag = true)
  {
    $this->_mailer->SMTPKeepAlive = $flag;
  }

  /**
   * Retrieve the subject of the message
   * @return string
   */
  public function GetSubject()
  {
    return $this->_mailer->Subject;
  }

  /**
   * Set the subject of the message
   * @param string $subject
   */
  public function SetSubject($subject)
  {
    $this->_mailer->Subject = $subject;
  }

  /**
   * Get the SMTP server timeout (in seconds).
   * @return int
   */
  public function GetSMTPTimeout()
  {
    return $this->_mailer->Timeout;
  }

  /**
   * Set the SMTP server timeout in seconds (for the SMTP mailer)
   * This function may not work with the win32 version.
   * @param int $timeout
   * @see Mailer::SetMailer
   */
  public function SetSMTPTimeout($timeout)
  {
    $this->_mailer->Timeout = $timeout;
  }

  /**
   * Get the SMTP username
   * @return string
   */
  public function GetSMTPUsername()
  {
    return $this->_mailer->Username;
  }

  /**
   * Set the SMTP Username.
   *
   * This is only used when using the SMTP mailer with SMTP authentication.
   * @param string $username
   * @see Mailer::SetMailer
   */
  public function SetSMTPUsername($username)
  {
    $this->_mailer->Username = $username;
  }

  /**
   * Get the number of characters used in word wrapping.  0 indicates that no word wrapping
   * will be performed.
   * @return int
   */
  public function GetWordWrap()
  {
    return $this->_mailer->WordWrap;
  }

  /**
   * Set word wrapping on the body of the message to the given number of characters
   * @param int $chars
   */
  public function SetWordWrap($chars)
  {
    $chars = max(0,min(1000,$chars));
    $this->_mailer->WordWrap = $chars;
  }

  /**
   * Add a "To" address.
   * @param string $address The email address
   * @param string $name    The real name
   * @return bool true on success, false if address already used
   */
  public function AddAddress($address, $name = '')
  {
    return $this->_mailer->AddAddress($address, $name);
  }

  /**
   * Add an attachment from a path on the filesystem
   * @param string $path Complete file specification to the attachment
   * @param string $name Set the attachment name
   * @param string $encoding File encoding (see $encoding)
   * @param string $type (mime type for the attachment)
   * @return bool true on success, false on failure.
   */
  public function AddAttachment($path, $name = '', $encoding = 'base64', $type = 'application/octet-stream')
  {
    return $this->_mailer->AddAttachment($path, $name, $encoding, $type);
  }

  /**
   * Add a "BCC" (Blind Carbon Copy) address
   * @param string $addr The email address
   * @param string $name The real name.
   * @return bool true on success, false on failure.
   */
  public function AddBCC($addr, $name = '')
  {
    $this->_mailer->AddBCC($addr, $name);
  }

  /**
   * Add a "CC" (Carbon Copy) address
   * @param string $addr The email address
   * @param string $name The real name.
   * @return bool true on success, false on failure.
   */
  public function AddCC($addr, $name = '')
  {
    $this->_mailer->AddCC($addr, $name);
  }

  /**
   * Add a custom header to the output email
   *
   * i.e: $obj->AddCustomHeader('X-MYHEADER: some-value');
   * @param string $header
   */
  public function AddCustomHeader($header)
  {
    $this->_mailer->AddCustomHeader($header);
  }

  /**
   * Add an embedded attachment.  This can include images, sounds, and
   * just about any other document.  Make sure to set the $type to an
   * image type.  For JPEG images use "image/jpeg" and for GIF images
   * use "image/gif".
   * @param string $path Path to the attachment.
   * @param string $cid Content ID of the attachment.  Use this to identify
   *        the Id for accessing the image in an HTML form.
   * @param string $name Overrides the attachment name.
   * @param string $encoding File encoding (see $Encoding).
   * @param string $type File extension (MIME) type.
   * @return bool
   */
  public function AddEmbeddedImage($path, $cid, $name = '', $encoding = 'base64', $type = 'application/octet-stream')
  {
    return $this->_mailer->AddEmbeddedImage($path, $cid, $name, $encoding, $type);
  }

  /**
   * Add a "Reply-to" address.
   * @param string $addr
   * @param string $name
   * @return bool
   */
  public function AddReplyTo($addr, $name = '')
  {
    $this->_mailer->AddReplyTo($addr, $name);
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
  public function AddStringAttachment($string, $filename, $encoding = 'base64', $type = 'application/octet-stream')
  {
    $this->_mailer->AddStringAttachment($string, $filename, $encoding, $type);
  }

  /**
   * Clear all recipients in the To list
   * @see Mailer::AddAddress
   */
  public function ClearAddresses()
  {
    $this->_mailer->ClearAddresses();
  }

  /**
   * Clear all recipients in the To, CC and BCC lists
   * @see Mailer::AddAddress
   * @see Mailer::AddCC
   * @see Mailer::AddBCC
   */
  public function ClearAllRecipients()
  {
    $this->_mailer->ClearAllRecipients();
  }

  /**
   * Clear all attachments
   * @see Mailer::AddAttachment
   * @see Mailer::AddStringAttachment
   * @see Mailer::AddEmbeddedImage
   */
  public function ClearAttachments()
  {
    $this->_mailer->ClearAttachments();
  }

  /**
   * Clear all recipients on the BCC list
   * @see Mailer::AddCC
   */
  public function ClearBCCs()
  {
    $this->_mailer->ClearBCCs();
  }

  /**
   * Clear all recipients on the CC list
   * @see Mailer::AddCC
   */
  public function ClearCCs()
  {
    $this->_mailer->ClearCCs();
  }

  /**
   * Clear all custom headers
   * @see Mailer::AddCustomHeader
   */
  public function ClearCustomHeaders()
  {
    $this->_mailer->ClearCustomHeaders();
  }

  /**
   * Clear the Reply-To list
   * @see Mailer::AddReplyTo
   */
  public function ClearReplyTos()
  {
    $this->_mailer->ClearReplyTos();
  }

  /**
   * Check whether there was an error on the last message send
   * @return bool
   */
  public function IsError()
  {
    return $this->_mailer->IsError();
  }

  /**
   * Set the message type to HTML.
   * @param bool $html
   */
  public function IsHTML($html = true)
  {
    return $this->_mailer->IsHTML($html);
  }

  /**
   * Check whether the mailer is set to 'mail'
   * @return bool
   */
  public function IsMail()
  {
    return $this->_mailer->IsMail();
  }

  /**
   * Check whether the mailer is set to 'sendmail'
   * @return bool
   */
  public function IsSendmail()
  {
    return $this->_mailer->IsSendmail();
  }

  /**
   * Check whether the mailer is set to 'SMTP'
   * @return bool
   */
  public function IsSMTP()
  {
    return $this->_mailer->IsSMTP();
  }

  /**
   * Send the current message using all current settings.
   *
   * This method might throw an exception if $exceptions were enabled
   *  in the constructor (which they are, by default)
   *
   * @return bool
   * @see Mailer::__construct
   */
  public function Send()
  {
    return $this->_mailer->Send();
  }

  /**
   * Set the language for all error messages
   * @param string $lang_type
   */
  public function SetLanguage($lang_type)
  {
    return $this->_mailer->SetLanguage($lang_type);
  }

  /**
   * Close the SMTP connection
   * Only necessary when using the SMTP mailer with keepalive enabled.
   * @see Mailer::SetSMTPKeepAlive
   */
  public function SmtpClose()
  {
    return $this->_mailer->SmtpClose();
  }

  /**
   * Get the secure SMTP connection mode, or none
   * @return string
   */
  public function GetSMTPSecure()
  {
    return $this->_mailer->SMTPSecure;
  }

  /**
   * Set the secure SMTP connection mode, or none
   * @param string $value Valid values are "", "ssl" or "tls"
   */
  public function SetSMTPSecure($value)
  {
    $value = strtolower($value);
    if ($value == '' || $value == 'ssl' || $value == 'tls') $this->_mailer->SMTPSecure = $value;
  }
} // class
