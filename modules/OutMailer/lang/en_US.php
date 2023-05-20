<?php
/*
OutMailer module default (en_US) strings translation
Copyright (C) 2004-2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

This file is a component of CMS Made Simple module OutMailer.

This OutMailer module is free software; you may redistribute it and/or modify
it under the terms of the GNU Affero General Public License as published
by the Free Software Foundation; either version 3 of that license, or
(at your option) any later version.

This OutMailer module is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU Affero General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <http://www.gnu.org/licenses/licenses.html#AGPL>.
*/

$lang = [
 'active' => 'Active',
 'add_gate' => 'Add new processor',
 'add_parameter' => 'Add parameter',
 'alias' => 'Alias',
 'always' => 'Always',
 'apiname' => 'API Name',
 'apply' => 'Apply',
 'batchgap' => 'Inter-Batch Delay',
 'batchsize' => 'Emails-Batch Size',
// TODO maybe UI-modifyable templates substitutable for these notices
 'bodybccnotice' => <<<'EOS'

This has been sent as a blind-courtesy-copy (BCC) to you.

Original message:

EOS
,
 'bodyccnotice' => <<<'EOS'

This has been sent as a courtesy-copy (CC) to you.

Original message:

EOS
,
 'cancel' => 'Cancel',
 'charset' => 'Character Set',
 'confirm_property' => 'Are you sure about removing those property(ies)?',
 'confirm_sendtestmail' => 'Are you sure?', //TODO better prompt
 'confirm_settings' => 'Are you sure about saving the values entered here?',
 'confirm_uninstall' => 'Are you sure about removing the OutMailer module?',
 'core' => 'Core',
 'days_1' => '1 Day',
 'days_counted' => '%s Days',
 'default_platform' => 'Default Email Processor',
 'delete_tip' => 'Delete selected parameter(s)',
 'delete' => 'Delete',
 'description' => 'Description',
 'enabled' => 'Enabled',
 'encrypt' => 'Encrypt',
 'error_badtestaddress' => 'Invalid address was specified for the test message',
 'error_notestaddress' => 'No address was specified to receive the test message',
 'external' => 'External',
 'file' => 'Files',
 'from' => 'From Address',
 'fromuser' => 'From Name',
 'gate_legend' => '%s', //%s Processor',
 'help' => 'Help',
 'host' => 'Hostname',
 'hours_1' => '1 Hour',
 'hours_counted' => '%s Hours',
 'info_active' => 'Check this to make this platform the default',
 'info_addgate' => 'TODO',
 'info_alias' => 'A unique identifier for this platform, used for all internal processing. If left blank, an alias will be derived from the title.',
//'info_batchgap' => 'Select the length of time to "pause" between sending batches of messages',
//'info_batchsize' => 'Enter the default maximum number of messages to be sent in a single batch, when batch-sending is used. A value of 0 will mean that no maximum applies.',
 'info_charset' => 'Also known as encoding, this will be used to indicate to the email reader-application how to interpret the non-ASCII characters in the message. Common values are "UTF-8" and "ISO-8859".',
 'info_client' => 'The public token to be used by this module for engaging with the identity provider. Obtained from the provider. Usually a sequence of chars tough to guess by third parties.',
 'info_desc' => 'TBA',
 'info_dnd' => 'You can change parameters\' order by dragging row(s).',
 'info_email' => 'TBA',
 'info_enabled' => 'Check this to enable this platform (whether or not it&apos;s the default)',
 'info_from' => 'The address that this module will use to send email messages. This cannot just be any email address. It must match the domain that CMSMS is providing. Specifying a personal email address from a different domain is known as "relaying" and will most probably result in emails not being sent, or not being accepted by the recipient email server. A good typical example for this field is noreply@mydomain.com',
 'info_fromuser' => 'A name to be associated with sent messages. This name may be anything but should reasonably correspond to the email address. Often, "Do Not Reply" is used.',
 'info_host' => 'When using the SMTP mailer, this option specifies the hostname (or IP address) of the SMTP server to use when sending email. You might need to contact your host for the proper value.',
 'info_mailer' => 'This choice controls how CMSMS will send mail. Using PHP\'s mail function, sendmail, or by communicating directly with an SMTP server.<br><br>The "PHP" option passes through to sendmail (or a variant of that), or to smpt, depending on the server OS.<br><br>The "sendmail" option should work on most properly-configured self-hosted Linux servers. However it might not work on shared hosts.<br><br>The "smtp" option requires configuration information from your host.<br><br>The "test" option is for investigating system setup, no mail is acutally sent.',
 'info_modpassword' => 'A secure pass-phrase for restricting access to sensitive data', //' The phrase must be eight or more characters long, and must comply with the site\'s passwords-policy (content, repetition).
 'info_module' => 'This module provides basic email-sending capability for the site. The module settings must be properly configured to match the site host\'s requirements.',
 'info_outmailer1' => 'Use information provided by the website host organisation to tailor these settings.',
 'info_outmailer2' => 'If the test message is not received, you should check/update the email settings, and if appropriate, contact the site host organisation, for further assistance.',
 'info_password' => 'This is the password for connecting to the SMTP server if SMTP authentication is being used.',
 'info_port' => 'When using the SMTP mailer this option specifies the integer port number for the SMTP server. In most cases this value is 25 for plaintext messages, or 465 for implicit TLS encryption, or 587 for STARTTLS encryption. You will probably need to engage with the site host to get the proper value.',
 'info_provider' => 'If Oauth authorisation is to be used, select the relevant one of the named providers, or select \'Other\' and enter the actual (supported) provider\'s name in the following input.',
 'info_secret' => 'The private token used to interact with the identity provider. 64 random hexadecimal characters would be a good choice for this.',
 'info_secure' => 'This option, for use with SMTP authentication, specifies the encryption mechanism to use when communicating with the SMTP server. The site host should provide the value for this setting, if SMTP authentication is being used.',
 'info_sendmail' => 'To use the "sendmail" mailer method, enter here the complete fileystem path of the sendmail program/application. A typical value for this field is "/usr/sbin/sendmail". This option is typically not used on windows hosts.<br><br><strong>Note:</strong> To use this method the host must allow the popen and pclose PHP functions which are often disabled on shared hosts.',
 'info_single' => 'When a message has > 1 recipient, send to each of them separately, instead of one message to all of them.<br> If &quot;Always&quot; is selected, a note will be added to the message sent to CC and BCC addresses, indicating its status as a copy.<br> If &quot;Any-CC/BCC&quot; is selected, individual message(s) will be sent only if there is any CC and/or BCC address. Otherwise, a single message to all To addresses.',
 'info_smtpauth' => 'When using the SMTP mailer, this option indicates that the SMTP server requires authentication to send emails. You then must specify (at least) a username and password. The site host should indicate whether SMTP authentication is required, and if so, provide a username and password, and optionally an encryption method.<br><br><strong>Note:</strong>strong> SMTP authentication is required if your domain is using Google apps for email.',
 'info_sure' => 'Be very sure about what you\'re doing, before modifying anything except title(s) and/or value(s)!',
 'info_testaddress' => 'Enter a valid address to receive the test message',
 'info_timeout' => ' When using the SMTP mailer, this option specifies the number of seconds before an attempted connection to the SMTP server will fail. A typical value for this setting is 60.<br><br><strong>Note:</strongstrong> If a longer value is necessary here, it probably indicates an underlying DNS, routing or firewall problem, and you might need to contact the site host.',
 'info_title' => 'The platform-identifier to be used for public display',
 'info_urlcheck' => 'Refer to the <a href="%s" target="_blank">%s API</a> for details',
 'info_username' => 'This is the username for connecting to the SMTP server if SMTP authentication is enabled.',
 'internal' => 'Internal',
 'lbl_client' => 'Client ID',
 'lbl_email' => 'User Email', // for oauth email address see also 'from'
 'lbl_otherprovider' => 'Other Provider',
 'lbl_provider' => 'Identity Provider',
 'lbl_secret' => 'Secret',
 'mailer' => 'Mail Sender',
 'mailtest_body' => '<h2 style=&quot;color:green;&quot;>Greetings</h2><p>This message was sent to confirm the validity of a website&quot;s email-settings.<br>Everything appears to be working as intended.<br>However, if you did not expect this email, please contact the site administrator via  %s.</p>',
 'mailtest_subject' => 'CMSMS Mail test message',
 'mailtest_success' => 'Test email was sent. Check whether it has been received.',
 'modpassword' => 'Master Passphrase',
 'module' => 'Module', // tab title
 'na' => 'Not Applicable',
 'never' => 'Never',
 'nocopies' => 'Any CC or BCC',
 'no_platforms' => 'No email processor is available',
 'none' => 'None',
 'password' => 'Password',
 'operation' => 'Operation', // tab title
 'oauth_legend' => 'OAuth Settings',
 'other' => 'Other',
 'port' => 'Port',
 'postinstall_notice' => 'Appropriate email settings must be recorded. Review the initial settings in the OutMailer module, before sending email.',
 'postinstall_title' => 'Appropriate Email Settings',
 'postinstall' => 'The OutMailer module was successfully installed. Remember: appropriate settings must be recorded, before sending email.',
 'postuninstall' => 'The OutMailer module was un-installed',
 'publicname' => 'Email Sender',
 'publictip' => 'Settings for sending email initiated from this website',
 'secure' => 'Encryption Method',
 'select' => 'Select',
 'sendmail_legend' => 'Sendmail Settings',
 'sendmail' => 'Command',
 'sendtest' => 'Send',
 'settings_title' => 'Email Settings', // admin label
 'single' => 'Send Individual Emails',
 'smtp_legend' => 'SMTP Settings',
 'smtpauth' => 'Authentication is Required',
 'specific_legend' => 'Other', //'This Module',
 'ssl' => 'SSL (deprecated)',
 'starttls' => 'STARTTLS',
 'submit' => 'Submit',
 'test' => 'Test',
 'testaddress' => 'Email Address',
 'testonly' => 'Test',
 'timeout' => 'Time-out (seconds)',
 'title' => 'Title',
 'tls' => 'TLS',
 'username' => 'Username / Account',
 'value' => 'Value',
] + $lang;
/*
'error_frominvalid' => 'The "from" address specified is not a valid email address',
'error_fromrequired' => 'A "from" address is required',
'error_hostrequired' => 'A host name is required when using the SMTP mailer',
*/
