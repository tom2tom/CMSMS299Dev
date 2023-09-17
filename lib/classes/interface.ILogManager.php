<?php
/*
AdminLog management interface
Copyright (C) 2017-2023 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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
namespace CMSMS;

use CMSMS\Log\logfilter;

interface ILogManager
{
	public function info(string $msg, string $subject = '', $item_id = 0);
	public function notice(string $msg, string $subject = '');
	public function warning(string $msg, string $subject = '');
	public function error(string $msg, string $subject = '');
	// for passthru to embedded storage class
	public function query(logfilter $filter);
	public function clear();
	public function clear_older_than(int $time);
}
