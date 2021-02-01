<?php
/*
This file is part of CMS Made Simple module: CMSMailer
Copyright (C) 2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Refer to licence and other details at the top of file CMSMailer.module.php
More info at http://dev.cmsmadesimple.org/projects/cmsmailer
*/

function sendinblue_gateway_spacedloader($classname)
{
    $classname = ltrim($classname, ' \\');
    $p = strpos($classname, '\\');
    if ($p !== false) {
        $space = substr($classname, 0, $p);
        if ($space === 'SendinBlue') {
            $exp = str_replace('SendinBlue\\Client', 'lib', $classname);
            $path = __DIR__.DIRECTORY_SEPARATOR.strtr($exp, '\\', DIRECTORY_SEPARATOR).'.php';
            if (is_file($path)) {
                include_once $path;
            }
        }
    }
}

spl_autoload_register('sendinblue_gateway_spacedloader');
