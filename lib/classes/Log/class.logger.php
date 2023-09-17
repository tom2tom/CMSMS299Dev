<?php
/*
Class to construct and save an admin log record.
Copyright (C) 2022-2023 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful, but
WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/
namespace CMSMS\Log;

use CMSMS\AppState;
use CMSMS\ILogManager;
use CMSMS\Log\logrecord;
use CMSMS\Utils;
use Throwable;
use function get_userid;
use function get_username;

class logger implements ILogManager
{
    private $_storage;
    private $_installing;

    /*
     * @param mixed $store
     */
    public function __construct($store)
    {
        $this->_storage = $store;
        $this->_installing = AppState::test(AppState::INSTALL);
    }

    protected function get_common_parms()
    {
        $parms = ['user_id' => '', 'username' => '', 'ip_addr' => ''];
        if (!$this->_installing) {
            $user_id = get_userid(false);
            if ($user_id) {
                $parms['user_id'] = $user_id;
                $parms['username'] = get_username(false);
                $parms['ip_addr'] = Utils::get_real_ip();
            }
        }
        return $parms;
    }

    public function info(string $msg, string $subject = '', /*mixed */$item_id = 0)
    {
        $parms = $this->get_common_parms() +
            ['severity'=>logrecord::TYPE_MSG, 'subject'=>$subject, 'message'=>$msg, 'item_id'=>$item_id];
        try {
            $rec = new logrecord($parms);
            $this->_storage->save($rec);
        } catch (Throwable $t) {
            //TODO
        }
    }

    public function notice(string $msg, string $subject = '')
    {
        $parms = $this->get_common_parms() +
            ['severity'=>logrecord::TYPE_NOTICE, 'message'=>$msg, 'subject'=>$subject];
        try {
            $rec = new logrecord($parms);
            $this->_storage->save($rec);
        } catch (Throwable $t) {
            //TODO
        }
    }

    public function warning(string $msg, string $subject = '')
    {
        $parms = $this->get_common_parms() +
            ['severity'=>logrecord::TYPE_WARNING, 'message'=>$msg, 'subject'=>$subject];
        try {
            $rec = new logrecord($parms);
            $this->_storage->save($rec);
        } catch (Throwable $t) {
            //TODO
        }
    }

    public function error(string $msg, string $subject = '')
    {
        $parms = $this->get_common_parms() +
            ['severity'=>logrecord::TYPE_ERROR, 'message'=>$msg, 'subject'=>$subject];
        try {
            $rec = new logrecord($parms);
            $this->_storage->save($rec);
        } catch (Throwable $t) {
            $here = 1; //TODO
        }
    }

    public function query(logfilter $filter)
    {
        return $this->_storage->query($filter);
    }

    public function clear()
    {
        $this->_storage->clear();
    }

    public function clear_older_than(int $time)
    {
        $this->_storage->clear_older_than($time);
    }
}
