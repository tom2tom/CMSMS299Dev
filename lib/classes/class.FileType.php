<?php
/*
Filetypes enumerator class
Copyright (C) 2016-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple. 
If not, see <https://www.gnu.org/licenses/>.
*/
namespace CMSMS;

use CMSMS\BasicEnum;

/**
 * A reflectable class that enumerates file-types.
 *
 * @package CMS
 * @license GPL
 * @since  2.2
 */
final class FileType extends BasicEnum
{
    const IMAGE     = 1;
    const AUDIO     = 2;
    const VIDEO     = 3;
    const MEDIA     = 4; //any of the above
    const DOCUMENT  = 5; //'office', plaintext, see also TEXT
    const SHOWABLE  = 9; //any of the above
    const ARCHIVE   = 15;
    const XML       = 20; //includes [x]html[5]
    const ML        = 20; //alias
    const PHP       = 30; //php[45s]?, phtml
    const SCRIPT    = 31; //js & the like
    const TEMPLATE  = 32;
    const STYLE     = 33; //css, sass, less etc
    const FONT      = 34;
    const EXE       = 35; //executable shell script
    const CODE      = 37; //30|31|sql
    const LAYOUT    = 38; //32|33
    const OPERATION = 39; //any of the 30's
    const TEXT      = 40; //plaintext DOCUMENT's, XML, OPERATION
    const NONE      = 0; //careful! falsy matches
    const ANY       = 99;
    const ALL       = 99; //alias
    const FILE      = 99; //alias
    //corresponding deprecated names
    const TYPE_IMAGE    = 1;
    const TYPE_AUDIO    = 2;
    const TYPE_VIDEO    = 3;
    const TYPE_MEDIA    = 4;
    const TYPE_DOCUMENT = 5;
    const TYPE_ARCHIVE  = 15;
    const TYPE_XML      = 20;
    const TYPE_ANY      = 99;

    private function __construct() {}
    private function __clone() {}
}
