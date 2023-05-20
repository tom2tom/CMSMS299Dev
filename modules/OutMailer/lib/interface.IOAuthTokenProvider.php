<?php
/*
This file is part of CMS Made Simple module: OutMailer
Copyright (C) 2023 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Refer to licence and other details at the top of file OutMailer.module.php
More info at http://dev.cmsmadesimple.org/projects/outmailer
*/
namespace OutMailer;

/**
 * IOAuthTokenProvider - OAuth2 token provider interface
 */
interface IOAuthTokenProvider
{
    /**
     * Generate a base64-encoded OAuth2 token which has not expired
     *
     * @param string $from The string to be processed, in the form
     * "user=<user_email_address>\001auth=Bearer <access_token>\001\001"
     * @return string
     */
    public function getOauth64(string $from): string;
}
