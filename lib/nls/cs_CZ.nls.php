<?php
#Czech translation for CMS Made Simple
#Copyright (C) 2004-2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
#This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>
#
#This program is free software; you can redistribute it and/or modify
#it under the terms of the GNU General Public License as published by
#the Free Software Foundation; either version 3 of that license, or
#(at your option) any later version.
#
#This program is distributed in the hope that it will be useful,
#but WITHOUT ANY WARRANTY; without even the implied warranty of
#MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#GNU General Public License for more details.
#You should have received a copy of the GNU General Public License
#along with this program. If not, see <https://www.gnu.org/licenses/>.

#NLS (National Language System) array.

#The basic idea and values was taken from then Horde Framework (http://horde.org)
#The original filename was horde/config/nls.php.
#The modifications to fit it for Gallery were made by Jens Tkotz
#(http://gallery.meanalto.com)

#Ideas from Gallery's implementation made to CMS by Ted Kulp

#
#Created by: Ted Kulp <tedkulp@users.sf.net>
#Maintained by: Petr Jelinek <pjelinek@seznam.cz>
#This is the default language

#Native language name
$nls['language']['cs_CZ'] = 'Česky';
$nls['englishlang']['cs_CZ'] = 'Czech';

#Possible aliases for language
$nls['alias']['cs'] = 'cs_CZ';
$nls['alias']['czech'] = 'cs_CZ' ;
$nls['alias']['cze'] = 'cs_CZ' ;
$nls['alias']['cs-CS'] = 'cs_CZ' ; // IE SUX
$nls['alias']['cs-CZ'] = 'cs_CZ' ; // IE SUX
$nls['alias']['cs_CS'] = 'cs_CZ' ;
$nls['alias']['cs_CZ.WINDOWS-1250'] = 'cs_CZ' ;
$nls['alias']['cs_CZ.ISO8859-2'] = 'cs_CZ' ;

#Possible locale for language
$nls['locale']['cs_CZ'] = 'cs_CZ.utf8,cs_CZ.utf-8,cs_CZ.UTF-8,cs_CZ,czech,Czech_Czech Republic.1250';

#Encoding of the language
$nls['encoding']['cs_CZ'] = 'UTF-8';

#Location of the file(s)
$nls['file']['cs_CZ'] = array(__DIR__.'/cs_CZ/admin.inc.php');

#Language setting for HTML area
# Only change this when translations exist in HTMLarea and plugin dirs
# (please send language files to HTMLarea development)

$nls['htmlarea']['cs_CZ'] = 'cz';
?>
