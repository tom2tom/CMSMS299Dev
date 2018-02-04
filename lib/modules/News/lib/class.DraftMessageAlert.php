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

class DraftMessageAlert extends \CMSMS\AdminAlerts\TranslatableAlert
{
    public function __construct($count)
    {
        parent::__construct([ 'Approve News'] );
        $this->name = __CLASS__;
        $this->priority = self::PRIORITY_LOW;
        $this->titlekey = 'title_draft_entries';
        $this->module = 'News';
        $this->msgkey = 'notify_n_draft_items';
        $this->msgargs = $count;
    }
}