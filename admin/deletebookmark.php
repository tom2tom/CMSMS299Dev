<?php
#...
#Copyright (C) 2004-2018 Ted Kulp <ted@cmsmadesimple.org>
#This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>
#
#This program is free software; you can redistribute it and/or modify
#it under the terms of the GNU General Public License as published by
#the Free Software Foundation; either version 2 of the License, or
#(at your option) any later version.
#
#This program is distributed in the hope that it will be useful,
#but WITHOUT ANY WARRANTY; without even the implied warranty of
#MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#GNU General Public License for more details.
#You should have received a copy of the GNU General Public License
#along with this program. If not, see <https://www.gnu.org/licenses/>.
#
#$Id$

$CMS_ADMIN_PAGE=1;

//require_once("../lib/include.php");
//require_once("../lib/classes/class.bookmark.inc.php");
$urlext='?'.CMS_SECURE_PARAM_NAME.'='.$_SESSION[CMS_USER_KEY];

check_login();

$bookmark_id = -1;
if (isset($_GET["bookmark_id"]))
{
	$bookmark_id = $_GET["bookmark_id"];

	$result = false;

	$bookops = cmsms()->GetBookmarkOperations();
	$markobj = $bookops->LoadBookmarkByID($bookmark_id);

	if ($markobj)
	{
		$result = $markobj->Delete();
	}

}

redirect("listbookmarks.php".$urlext);


?>
