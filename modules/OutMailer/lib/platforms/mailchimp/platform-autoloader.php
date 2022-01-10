<?php
/*
This file is part of CMS Made Simple module: OutMailer
Copyright (C) 2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Refer to licence and other details at the top of file OutMailer.module.php
More info at http://dev.cmsmadesimple.org/projects/outmailer
*/

function mailchimp_gateway_spacedloader($classname)
{
    $p = strpos($classname, '\\', 1);
    if ($p !== false) {
        if ($classname[0] === '\\') {
            $classname = substr($classname, 1);
            --$p;
        }
        $space = substr($classname, 0, $p);
        if ($space === 'Mailchimp') {
            $path = strtr($classname, '\\', DIRECTORY_SEPARATOR);
            $Y = basename($path);
            $Z = str_replace('Mailchimp_', '', $Y);
            $path = __DIR__.DIRECTORY_SEPARATOR.str_replace($Y, $Z, $path).'.php';
            if (is_file($path)) {
                include_once $path;
            }
        }
    } elseif (strncmp($classname, 'Mailchimp_', 10) == 0 || strncmp($classname, '\\Mailchimp_', 11) == 0) {
        $Z = str_replace('Mailchimp_', '', $classname);
        $path = __DIR__.DIRECTORY_SEPARATOR.'Mailchimp'.DIRECTORY_SEPARATOR.trim($Z, ' \\').'.php';
        if (is_file($path)) {
            include_once $path;
        }
    } elseif ($classname === 'Mailchimp' || $classname === '\\Mailchimp') {
        include_once __DIR__.DIRECTORY_SEPARATOR.'Mailchimp.php';
    }
}

spl_autoload_register('mailchimp_gateway_spacedloader');
