<?php
/*
CMSMailer module default (en_US) strings translation
Copyright (C) 2004-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

This file is a component of CMS Made Simple module CMSMailer.

This CMSMailer module is free software; you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as published
by the Free Software Foundation; either version 3 of that license, or
(at your option) any later version.

This CMSMailer module is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU Affero General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <http://www.gnu.org/licenses/licenses.html#AGPL>.
*/

$lang = [
 'cancel' => 'Cancel',
 'charset' => 'Character Set',
 'confirm_sendtestmail' => 'Are you sure?', //TODO better prompt
 'confirm_settings' => 'Are you sure about saving the values entered here?',
 'confirm_uninstall' => 'Are you sure about removing the CMSMailer module?',
 'core' => 'Core',
 'error_notestaddress' => 'No address was specified to receive the test message',
 'error_badtestaddress' => 'Invalid address was specified for the test message',
 'from' => 'From Address',
 'fromuser' => 'From Name',
 'help_module' => 'This module provides basic email-sending capability for the site. The module settings must be properly configured to match the site host\'s requirements.',
 'host' => 'Hostname',
 'info_charset' => 'Also known as encoding, this will be used to indicate to the email reader-application how to interpret the non-ASCII characters in the message. Common values are "UTF-8" and "ISO-8859".',
 'info_cmsmailer1' => 'Use information provided by the website host organisation to tailor these settings.',
 'info_cmsmailer2' => 'If the test message is not sent properly, you should check/update the email settings, and if appropriate, contact the site host organisation, for further assistance.',
 'info_from' => 'The address that this module will use to send email messages. This cannot just be any email address. It must match the domain that CMSMS is providing. Specifying a personal email address from a different domain is known as "relaying" and will most probably result in emails not being sent, or not being accepted by the recipient email server. A good typical example for this field is noreply@mydomain.com',
 'info_fromuser' => 'A name to be associated with sent messages. This name may be anything but should reasonably correspond to the email address. Often, "Do Not Reply" is used.',
 'info_host' => 'When using the SMTP mailer, this option specifies the hostname (or IP address) of the SMTP server to use when sending email. You might need to contact your host for the proper value.',
 'info_mailer' => 'This choice controls how CMSMS will send mail. Using PHP\'s mail function, sendmail, or by communicating directly with an SMTP server.<br /><br />The "mail" option should work on most shared hosts. However it will almost certainly not work on most self-hosted windows installations.<br /><br />The "sendmail" option should work on most properly-configured self-hosted Linux servers. However it might not work on shared hosts.<br /><br />The "smtp" option requires configuration information from your host.',
 'info_password' => 'This is the password for connecting to the SMTP server if SMTP authentication is being used.',
 'info_port' => 'When using the SMTP mailer this option specifies the integer port number for the SMTP server. In most cases this value is 25 for plaintext messages, or 443 for secured. You might need to contact your host to get the proper value.',
 'info_secure' => 'This option, for use with SMTP authentication, specifies the encryption mechanism to use when communicating with the SMTP server. The site host should provide the value for this setting, if SMTP authentication is being used.',
 'info_sendmail' => 'To use the "sendmail" mailer method, enter here the complete fileystem path of the sendmail program/application. A typical value for this field is "/usr/sbin/sendmail". This option is typically not used on windows hosts.<br /><br /><strong>Note:</strong> To use this method  the host must allow the popen and pclose PHP functions which are often disabled on shared hosts.',
 'info_smtpauth' => 'When using the SMTP mailer, this option indicates that the SMTP server requires authentication to send emails. You then must specify (at least) a username and password. The site host should indicate whether SMTP authentication is required, and if so, provide a username and password, and optionally an encryption method.<br /><br /><strong>Note:</strong>strong> SMTP authentication is required if your domain is using Google apps for email.',
 'info_testaddress' => 'Enter a valid address to receive the test message',
 'info_timeout' => ' When using the SMTP mailer, this option specifies the number of seconds before an attempted connection to the SMTP server will fail. A typical value for this setting is 60.<br /><br /><strong>Note:</strongstrong> If a longer value is necessary here, it probably indicates an underlying DNS, routing or firewall problem, and you might need to contact the site host.',
 'info_username' => 'This is the username for connecting to the SMTP server if SMTP authentication is enabled.',
 'mailer' => 'Mail Sender',
 'mailtest_body' => '<h2 style=&quot;color:green;&quot;>Greetings</h2><p>This message was sent from a website using <strong>CMS Made Simple</strong>, to confirm the validity of that site&apos;s email-settings.</p><p>Everything appears to be working as intended.</p><p>However, if you did not expect this email, please contact the website administrator.</p>',
 'mailtest_subject' => 'CMSMS Mail test message',
 'mailtest_success' => 'Test email was sent. Check whether it has been received.',
 'module' => 'This Module',
 'none' => 'None',
 'password' => 'Password',
 'port' => 'Port',
 'postinstall' => 'CMSMailer module was successfully installed. Remember: appropriate settings must be recorded, before sending mail.',
 'postuninstall' => 'CMSMailer module was un-installed',
 'publicname' => 'Email Sender',
 'publictip' => 'Settings for sending email initiated from the website',
 'secure' => 'Encryption Method',
 'sendmail' => 'Command',
 'sendmail_legend' => 'Sendmail Settings',
 'sendtest' => 'Send',
 'settings' => 'Settings',
 'settings_title' => 'Email Settings',
 'smtpauth' => 'Authentication is Required',
 'smtp_legend' => 'SMTP Settings',
 'ssl' => 'SSL',
 'submit' => 'Submit',
 'test' => 'Test',
 'testaddress' => 'Email Address',
 'timeout' => 'Time-out (seconds)',
 'tls' => 'TLS',
 'username' => 'Username / Account',
] + $lang;
/*
'error_frominvalid' => 'The "from" address specified is not a valid email address',
'error_fromrequired' => 'A "from" address is required',
'error_hostrequired' => 'A host name is required when using the SMTP mailer',
'error_mailnotset_notest' => 'Mail settings have not been saved. Cannot test',
*/
