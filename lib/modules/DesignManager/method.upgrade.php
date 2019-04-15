<?php
# DesignManager module upgrade process.
# Copyright (C) 2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

use CMSMS\Database\DataDictionary;
use DesignManager\Design;

if (!function_exists('cmsms')) exit;

if (version_compare($current_version,'1.1.6') <= 0) {
	$dict = new DataDictionary($db);

	$sqlarray = $dict->RenameTableSQL(CMS_DB_PREFIX.'layout_designs', CMS_DB_PREFIX.Design::TABLENAME);
	$dict->ExecuteSQLArray($sqlarray);
	$sqlarray = $dict->RenameTableSQL(CMS_DB_PREFIX.'layout_design_tplassoc', CMS_DB_PREFIX.Design::TPLTABLE);
	$dict->ExecuteSQLArray($sqlarray);
	$sqlarray = $dict->RenameTableSQL(CMS_DB_PREFIX.'layout_design_cssassoc', CMS_DB_PREFIX.Design::CSSTABLE);
	$dict->ExecuteSQLArray($sqlarray);
	//TODO update fields
}
