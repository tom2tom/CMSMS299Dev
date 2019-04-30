<?php
# DesignManager module action: export design
# Copyright (C) 2012-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

use DesignManager\Design;
use DesignManager\design_exporter;

if( !isset($gCms) ) exit;
if( !$this->CheckPermission('Manage Designs') ) return;

//$this->SetCurrentTab('designs');

if( !isset($params['design']) || $params['design'] == '' ) {
  $this->SetError($this->Lang('error_missingparam'));
  $this->Redirect($id,'defaultadmin'.$returnid);
}

try {
  // and the work...
  $the_design = Design::load($params['design']);
  $exporter = new design_exporter($the_design);
  $xml = $exporter->get_xml();

  // clear any output buffers.
  $handlers = ob_list_handlers();
  for ($cnt = 0, $n = count($handlers); $cnt < $n; $cnt++) { ob_end_clean(); }

  // headers
  header('Content-Description: File Transfer');
  header('Content-Type: application/force-download');
  header('Content-Disposition: attachment; filename='.munge_string_to_url($the_design->get_name()).'.xml');

  // output
  echo $xml;
  exit;
}
catch( Exception $e ) {
  $this->SetError($e->GetMessage());
  $this->RedirectToAdminTab();
}
