<?php
/*
Class defining folder-specific property values
Copyright (C) 2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
You should have received a copy of the GNU General Public License
along with this program. If not, see <https://www.gnu.org/licenses/>.
*/

namespace CMSMS;

class ProfileValue extends BasicEnum
{
    const NO = 0; //sometimes treated as boolean false
    const NONE = 0; // ditto
    const YES = 1;
    const BYGROUP = 2;
    const BYUSER = 3;
    // deprecated aliases from FilePickerProfile class
    const FLAG_NO = 0;
    const FLAG_NONE = 0;
    const FLAG_YES = 1;
    const FLAG_BYGROUP = 2;

    private function __construct() {}
    private function __clone() {}
}
