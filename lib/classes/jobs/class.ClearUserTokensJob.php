<?php
/*
Class ClearUserTokensJob for periodic cleanup of expired user-tokens
Copyright (C) 2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

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
namespace CMSMS\jobs;

use CMSMS\Async\CronJob;
use CMSMS\Async\RecurType;
use CMSMS\Lone;
use const CMS_DB_PREFIX;

class ClearUserTokensJob extends CronJob
{
    public function __construct()
    {
        parent::__construct();
        $this->name = 'Core\ClearUserTokens';
        $this->frequency = RecurType::RECUR_DAILY;
    }

    /**
     * @ignore
     * @return int 0|1|2 indicating execution status
      */
    public function execute()
    {
        $db = Lone::get('Db');
        $pref = CMS_DB_PREFIX;
        $limit = time();
        $sql = <<<EOS
SELECT user_id FROM {$pref}userprefs
WHERE preference='tokenstamp' AND `value` IS NOT NULL AND `value`<$limit
EOS;
        $users = $db->getCol($sql);
        if ($users) {
           $uj = implode(',', $users);
           $sql = <<<EOS
DELETE FROM {$pref}userprefs
WHERE user_id IN ($uj) AND (preference='token' OR preference='tokenstamp')
EOS;
           $db->execute($sql);
           return 2;
       }
       return 1;
    }
}
//\class_alias('CMSMS\jobs\ClearUserTokensJob', 'ClearUserTokensTask', false);
