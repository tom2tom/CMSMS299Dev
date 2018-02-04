<?php
#BEGIN_LICENSE
#-------------------------------------------------------------------------
# Module: CMSContentManager (c) 2013 by Robert Campbell
#         (calguy1000@cmsmadesimple.org)
#  A module for managing content in CMSMS.
#
#-------------------------------------------------------------------------
# CMS - CMS Made Simple is (c) 2004 by Ted Kulp (wishy@cmsmadesimple.org)
# This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>
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

class ContentAssistantFactory
{
	private $_content_obj;

	public function __construct(ContentBase $content_obj)
	{
		$this->_content_obj = $content_obj;
	}

	public function &getEditContentAssistant()
	{
		$classname = get_class($this->_content_obj);
		$n = 0;
		while( $n < 10 ) {
			$n++;
			$test = $classname.'EditContentAssistant';
			if( class_exists($test) ) {
				$obj = new $test($this->_content_obj);
				return $obj;
			}
			$classname = get_parent_class($classname);
			if( !$classname ) {
				$obj = null;
				return $obj;
			}
		}
		throw new CmsException('Too many levels of hierarchy without finding an assistant');
  }
} // end of class

#
# EOF
#

?>