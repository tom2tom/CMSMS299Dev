<?php
/*
Class to generate an admin-console alert indicating presence of draft news item(s)
Copyright (C) 2016-2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/
namespace News;

use CMSMS\AdminAlerts\TranslatableAlert;

class DraftMessageAlert extends TranslatableAlert
{
  public function __construct($count)
  {
    parent::__construct(['Approve News']);
    $this->name = __CLASS__;
    $this->module = 'News';
    $this->priority = parent::PRIORITY_LOW;
    $this->titlekey = 'prompt_draft_entries';
    $this->msgkey = 'notify_n_draft_items';
    $this->msgargs = $count;
  }
}
