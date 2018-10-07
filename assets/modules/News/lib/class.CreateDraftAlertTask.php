<?php
# Task: to ...
# Copyright (C) 2016-2018 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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
use const CMS_DB_PREFIX;

class CreateDraftAlertTask implements CmsRegularTask
{
  const PREFNAME = 'task1_lastrun';

  public function get_name()
  {
     $c = __CLASS__;
     $p = strrpos($c, '\\');
     return substr($c, $p+1);
  }

  public function get_description()
  {
    return '';
  }

  public function test($time = '')
  {
    if( !$time ) $time = time();
    $mod = cms_utils::get_module('News');
    $lastrun = (int) $mod->GetPreference(self::PREFNAME);
    return $lastrun <= ($time - 900); // hardcoded to quarter-hourly
  }

  public function on_success($time = '')
  {
    if( !$time ) $time = time();
    $mod = cms_utils::get_module('News');
    $mod->SetPreference(self::PREFNAME,$time);
  }

  public function on_failure($time = '') {}

  public function execute($time = '')
  {
    $db = CmsApp::get_instance()->GetDb();
    if( !$time ) $time = time();

    $query = 'SELECT count(news_id) FROM '.CMS_DB_PREFIX.'module_news n WHERE status != \'published\'
          AND (end_time IS NULL OR end_time > '.$now.')';
    $count = $db->GetOne($query);
    if( !$count ) return TRUE;

    $alert = new DraftMessageAlert($count);
    $alert->save();
    return TRUE;
  }
}
