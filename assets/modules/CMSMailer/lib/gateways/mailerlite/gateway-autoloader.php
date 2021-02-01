<?php
/*
This file is part of CMS Made Simple module: CMSMailer
Copyright (C) 2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Refer to licence and other details at the top of file CMSMailer.module.php
More info at http://dev.cmsmadesimple.org/projects/cmsmailer
*/

function mailerlite_gateway_spacedloader($classname)
{
    $classname = ltrim($classname, ' \\');
    $p = strpos($classname, '\\');
    if ($p !== false) {
        $space = substr($classname, 0, $p);
        if ($space === 'MailerLiteApi') {
            if (endswith($classname, 'MailerLite'))
                $path = __DIR__.DIRECTORY_SEPARATOR.'MailerLite.php';
            } else {
                $path = __DIR__.DIRECTORY_SEPARATOR.strtr($classname, '\\', DIRECTORY_SEPARATOR).'.php';
            if (is_file($path)) {
                include_once $path;
            }
        }
    }
}

spl_autoload_register('mailerlite_gateway_spacedloader');
