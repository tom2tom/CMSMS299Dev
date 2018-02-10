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

require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'include.php';
$urlext='?'.CMS_SECURE_PARAM_NAME.'='.$_SESSION[CMS_USER_KEY];

check_login();

$error = "";

$title= "";
if (isset($_POST["title"])) $title = trim(cleanValue($_POST["title"]));
$url = "";
if (isset($_POST["url"])) $url = trim(cleanValue($_POST["url"]));

if (isset($_POST["cancel"])) {
	redirect("listbookmarks.php".$urlext);
	return;
}

$userid = get_userid();

if (isset($_POST["addbookmark"]))
	{
	$validinfo = true;

	if ( $title == "" )
		{
		$error .= lang('nofieldgiven', array(lang('title')));
		$validinfo = false;
		}
		else if ( $url == "" )
		{
		$error .= lang('nofieldgiven', array(lang('url')));
		$validinfo = false;
		}

	if ($validinfo)
		{
		  $gCms = cmsms();
		$gCms->GetBookmarkOperations();
		$markobj = new Bookmark();
		$markobj->title = $title;
		$markobj->url = $url;
		$markobj->user_id=$userid;

		$result = $markobj->save();

		if ($result)
			{
			redirect("listbookmarks.php".$urlext);
			return;
			}
		else
			{
			$error .= lang('errorinsertingbookmark');
			}
		}
	}

include_once("header.php");

if ($error != "")
	{
		echo '<div class="pageerrorcontainer"><p class="pageerror">'.$error.'</p></div>';
	}
?>

<div class="pagecontainer">
	<div class="pageoverflow">
			<?php echo $themeObject->ShowHeader('addbookmark'); ?>
			<form method="post" action="addbookmark.php<?php echo $urlext?>">
       <div>
          <input type="hidden" name="<?php echo CMS_SECURE_PARAM_NAME ?>" value="<?php echo $_SESSION[CMS_USER_KEY] ?>" />
        </div>
				<div class="pageoverflow">
					<p class="pagetext"><?php echo lang('title')?>:</p>
					<p class="pageinput"><input type="text" name="title" maxlength="255" value="<?php echo $title?>" /></p>
				</div>
				<div class="pageoverflow">
					<p class="pagetext"><?php echo lang('url')?>:</p>
					<p class="pageinput"><input type="text" name="url" size="50" maxlength="255" value="<?php echo $url ?>" class="standard" /></p>
				</div>
				<div class="pageoverflow">
					<p class="pagetext">&nbsp;</p>
					<p class="pageinput">
						<input type="hidden" name="addbookmark" value="true" />
						<input type="submit" value="<?php echo lang('submit')?>" class="pagebutton" />
						<input type="submit" name="cancel" value="<?php echo lang('cancel')?>" class="pagebutton" />
					</p>
				</div>
			</form>
	</div>
</div>

<?php
include_once("footer.php");


?>
