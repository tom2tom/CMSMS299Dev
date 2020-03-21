<?php
# Task: generate a notice about draft news item(s)
# Copyright (C) 2016-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
# Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.
# This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# You should have received a copy of the GNU General Public License
# along with this program. If not, see <https://www.gnu.org/licenses/>.

namespace News;

use cms_utils;
use CmsApp;
use CmsRegularTask;
use News\DraftMessageAlert;
use const CMS_DB_PREFIX;

class CreateDraftAlertTask implements CmsRegularTask
{
  const LASTRUN_SITEPREF = 'News\\\\lastdraftalert';

  public function get_name()
  {
    return self::class;
  }

  public function get_description()
  {
    //$mod = ; return $mod->Lang('TODO');
    return 'A quarter-hourly task which generates a notice about draft news item(s)';
  }

  public function test($time = 0)
  {
    if( !$time ) $time = time();
    $mod = cms_utils::get_module('News');
    $lastrun = (int) $mod->GetPreference(self::LASTRUN_SITEPREF);
    return $lastrun <= ($time - 900); // hardcoded to quarter-hourly
  }

  public function on_success($time = 0)
  {
    if( !$time ) $time = time();
    $mod = cms_utils::get_module('News');
    $mod->SetPreference(self::LASTRUN_SITEPREF,$time);
  }

  public function on_failure($time = 0) {}

  public function execute($time = 0)
  {
    if( !$time ) $time = time();
    $db = CmsApp::get_instance()->GetDb();
    $query = 'SELECT COUNT(news_id) FROM '.CMS_DB_PREFIX.'module_news WHERE status = \'draft\' AND (end_time IS NULL OR end_time > '.$time.')';
    $count = $db->GetOne($query);
    if( $count ) {
        $alert = new DraftMessageAlert($count);
        $alert->save();
    }
    return TRUE;
  }
}
