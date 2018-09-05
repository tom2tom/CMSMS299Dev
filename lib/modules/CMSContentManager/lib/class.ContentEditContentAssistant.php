<?php
# Class ContentEditContentAssistant: for building content-edit-content assistant objects
# Copyright (C) 2013-2018 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

namespace CMSContentManager;

use cms_utils;
use CMSContentManager\EditContentAssistant;
use CMSMS\internal\Smarty;

class ContentEditContentAssistant extends EditContentAssistant
{
	// get javascript for editcontent for the Content object and its derived objects.
	public function getExtraCode()
	{
		$mod = cms_utils::get_module('CMSContentManager');
		$smarty = Smarty::get_instance();
		$tpl = $smarty->createTemplate( $mod->GetTemplateResource( 'content_editcontent_extra.tpl' ),null,null,$smarty );
		return $tpl->fetch();
	}
} // class
