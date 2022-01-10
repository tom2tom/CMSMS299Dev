<?php
/*
An enum for working with FolderControls.
Copyright (C) 2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as published
by the Free Software Foundation; either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU Affero General Public License
<http://www.gnu.org/licenses/licenses.html#AGPL> for more details.
*/
namespace CMSMS;

use CMSMS\BasicEnum;

/**
 * @since 2.99
 */
final class FolderOperationTypes extends BasicEnum
{
    const MKDIR = 1;
    const MKFILE = 2;
    const MODFILE = 2;
    const DELETE = 3;
    const VIEWFILE = 4;
    const LISTALL = 10;
    const SHOWHIDDEN = 20;
    const SHOWTHUMBS = 21;

    private function __construct() {}
    private function __clone() {}
}
