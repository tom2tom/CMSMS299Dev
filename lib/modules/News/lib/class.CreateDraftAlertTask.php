<?php
#BEGIN_LICENSE
#-------------------------------------------------------------------------
# (c) 2016 by Robert Campbell (calguy1000@cmsmadesimple.org)
#
#-------------------------------------------------------------------------
# CMS - CMS Made Simple is (c) 2005-2010 by Ted Kulp (wishy@cmsmadesimple.org)
# This projects homepage is: http://www.cmsmadesimple.org
#
#-------------------------------------------------------------------------
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
#
#-------------------------------------------------------------------------
#END_LICENSE

namespace News;

class CreateDraftAlertTask implements \CmsRegularTask
{
    public function get_name()
    {
        return basename(get_class($this));
    }

    public function get_description()
    {
        return $this->get_name();
    }

    public function test($time = '')
    {
        if( !$time ) $time = time();
        $mod = \cms_utils::get_module('News');
        $lastrun = (int) $mod->GetPreference('task1_lastrun');
        if( $lastrun >= ($time - 900) ) return FALSE; // hardcoded to 15 minutes
        return TRUE;
    }

    public function on_success($time = '')
    {
        IF( !$time ) $time = time();
        $mod = \cms_utils::get_module('News');
        $mod->SetPreference('task1_lastrun',$time);
    }

    public function on_failure($time = '') {}

    public function execute($time = '')
    {
        $db = \CmsApp::get_instance()->GetDb();
        if( !$time ) $time = time();

        $query = 'SELECT count(news_id) FROM '.CMS_DB_PREFIX.'module_news n WHERE status != \'published\'
                  AND (end_time IS NULL OR end_time > NOW())';
        $count = $db->GetOne($query);
        if( !$count ) return TRUE;

        $alert = new DraftMessageAlert($count);
        $alert->save();
        return TRUE;
    }
}
