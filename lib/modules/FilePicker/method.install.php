<?php
# Module: FilePicker - A CMSMS addon module to provide various support services.
# Copyright (C) 2016 Fernando Morgado <jomorg@cmsmadesimple.org>
# Copyright (C) 2016-2018 Robert Campbell <calguy1000@cmsmadesimple.org>
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

use FilePicker\ProfileDAO;

$db = $this->GetDb();
$taboptarray = ['mysqli' => 'ENGINE=MYISAM CHARACTER SET utf8 COLLATE utf8_general_ci'];
$dict = NewDataDictionary($db);

try {
    $flds = '
        id I AUTO PRIMARY KEY,
        name C(100) NOTNULL,
        data X(16384),
        create_date I,
        modified_date i';

    $sqlarray = $dict->CreateTableSQL(ProfileDAO::table_name(), $flds, $taboptarray);
    $dict->ExecuteSQLArray($sqlarray);
    $sqlarray = $dict->CreateIndexSQL(CMS_DB_PREFIX.'cmsfp_idx0', ProfileDAO::table_name(), 'name', [ 'UNIQUE' ] );
    $dict->ExecuteSQLArray($sqlarray);
}
catch(Exception $e) {
    return $e->getMessage();
}
