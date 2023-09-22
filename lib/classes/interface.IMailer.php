<?php
/*
Email sender interface
Copyright (C) 2022-2023 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/
namespace CMSMS;

/**
 * Derived substantially from the API of the PHPMailer class
 * @since 3.0
 */
interface IMailer
{
	public function reset();
	// this method replicates PHP's mail() function
	public function send_simple(string $to, string $subject, string $message, $additional_headers = [], string $additional_params = ''): bool;
	public function IsSingleAddressor(): bool; // 3.0+
	public function SetSingleSend(int $state = 1); // 3.0+

	// PHPMailer class methods (some to be deprecated?)
	public function GetAltBody();
	public function SetAltBody(string $txt);
	public function GetBody();
	public function SetBody(string $txt);
	public function GetCharSet();
	public function SetCharSet($charset);
	public function GetConfirmReadingTo();
	public function SetConfirmReadingTo(string $email);
	public function GetEncoding();
	public function SetEncoding(string $encoding);
	public function GetErrorInfo();
	public function GetFrom();
	public function SetFrom(string $email);
	public function GetFromName();
	public function SetFromName(?string $name);
	public function GetHelo();
	public function SetHelo(?string $helo);
	public function GetHostname();
	public function SetHostname(string $hostname);
	public function GetMailer();
	public function SetMailer(string $mailer);
	public function GetPriority();
	public function SetPriority(int $priority);
	public function GetSender();
	public function SetSender(?string $sender);
	public function GetSendmail();
	public function SetSendmail($path);
	public function GetWordWrap();
	public function SetWordWrap($chars);

	public function GetSMTPHost();
	public function SetSMTPHost($host);
	public function GetSMTPPassword();
	public function SetSMTPPassword($password);
	public function GetSMTPPort();
	public function SetSMTPPort($port);
	public function GetSMTPAuth();
	public function SetSMTPAuth($state = true);
	public function GetSMTPDebug();
	public function SetSMTPDebug($state = true);
	public function GetSMTPKeepAlive();
	public function SetSMTPKeepAlive($state = true);
	public function GetSMTPTimeout();
	public function SetSMTPTimeout($timeout);
	public function GetSMTPUsername();
	public function SetSMTPUsername($username);
	public function GetSMTPSecure();
	public function SetSMTPSecure($value);

	public function AddAddress(string $address, string $name = ''): bool;
	public function AddAttachment($path, $name = '', $encoding = 'base64', $type = 'application/octet-stream');
	public function AddBCC(string $addr, string $name = ''): bool;
	public function AddCC(string $addr, string $name = ''): bool;
	public function AddCustomHeader(string $header);
	public function AddEmbeddedImage($path, $cid, $name = '', $encoding = 'base64', $type = 'application/octet-stream');
	public function AddReplyTo($addr, $name = '');
	public function AddStringAttachment($string, $filename, $encoding = 'base64', $type = 'application/octet-stream');
	public function GetSubject();
	public function SetSubject(string $subject);
	public function ClearAddresses();
	public function ClearAllRecipients();
	public function ClearAttachments();
	public function ClearBCCs();
	public function ClearCCs();
	public function ClearCustomHeaders();
	public function ClearReplyTos();

	public function IsError();
	public function IsHTML(bool $state = true): void;
	public function IsMail();
	public function IsSendmail();
	public function IsSMTP();
	public function Send(); // TODO (int $batchsize = 0) // 3.0+
	public function SetLanguage($lang_type);
	public function SmtpClose();
	// OutMailer (at least) Oauth2 methods TODO to be refined
	public function getOAuth(): ?\OutMailer\IOAuthTokenProvider;
	public function setOAuth(?\OutMailer\IOAuthTokenProvider $oauth);
	public function getOAuthType(): ?string;
	public function setOAuthType(?string $val);
	public function GetOauthSender(): string;
	public function SetOauthSender(string $email);
	public function GetOauthClient(): string;
	public function SetOauthClient(string $val);
	public function GetOauthSecret(): string;
	public function SetOauthSecret(string $val);
}