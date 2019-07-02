<?php
# Class ContentEditContentAssistant: for building content-edit-content assistant objects
# Copyright (C) 2013-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

use CMSContentManager\EditContentAssistant;

class ContentEditContentAssistant extends EditContentAssistant
{
	// get javascript for editcontent for the Content object and its derived objects.
	public function getExtraCode()
	{
		return <<<EOS
<script type="text/javascript">
//<![CDATA[
$(function() {
  $('#design_id').change(function() {
    var v = $(this).val();
    //WHAT IS MISSING FROM HERE ??
  });
});
//]]>
</script>
EOS;
	}
} // class
