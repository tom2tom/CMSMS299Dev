<?php
/*
Class to allow custom handling of session-destruction
Copyright (C) 2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple. 
If not, see <https://www.gnu.org/licenses/>.
*/
namespace CMSMS\internal;

use Session as PHPSession;

/**
 * Class which extends PHP's session handling, specifically to support
 * processing of registered end-of-session callables
 * @since 2.99
 */
class Session extends PHPSession
{
    /**
     * @ignore
     */
    private $key;

    /**
     * @ignore
     */
    public function __construct()
    {
        $this->key = 'SESSION_'.md5(__FILE__).'_ENDERS';
        parent::__construct();
    }

    /**
     * Run registered handlers, if any, before cleanup
     * @param string $sessionId
     * @return bool indicating success
     */
    public function destroy(string $sessionId) : bool
    {
        $shutters = $_SESSION[$this->key] ?? [];
        foreach ($shutters as $handler) {
            if (is_callable($handler)) {
                $handler();
            }
        }
        return parent::destroy($sessionId);
    }

    /**
     * Supplement for PHP's minimally-useful session_register_shutdown()
     *
     * @param callable $handler
     */
    public function register_destroy_function(callable $handler)
    {
        if (!isset($_SESSION[$this->key])) {
            $_SESSION[$this->key] = [];
        }
        $_SESSION[$this->key][] = $handler;
    }
}
