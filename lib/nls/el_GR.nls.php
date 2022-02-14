<?php
#Greek translation for CMS Made Simple
#Copyright (C) 2004-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

#GR Greek
#Created by: Panagiotis Skarvelis <sl45sms@yahoo.gr>


#Native language name
#NOTE: Encode me with HTML escape chars like &#231; or &ntilde; so I work on every page
$nls['language']['el_GR'] = '&Epsilon;&lambda;&lambda;&eta;&nu;&iota;&kappa;&alpha;';
$nls['englishlang']['el_GR'] = 'Greek';

#Possible aliases for language
$nls['alias']['gr'] = 'el_GR';
$nls['alias']['greek'] = 'el_GR' ;
$nls['alias']['hellenic'] = 'el_GR' ;
$nls['alias']['el'] = 'el_GR' ;
$nls['alias']['el_GR.ISO8859-7'] = 'el_GR' ;

#Possible locale for language
$nls['locale']['el_GR'] = 'el_GR.utf8,el_GR.utf-8,el_GR.UTF-8,el_GR,greek,Greek_Greece.1253';

#Encoding of the language
$nls['encoding']['el_GR'] = 'UTF-8';

#Location of the file(s)
$nls['file']['el_GR'] = array(__DIR__.'/el_GR/admin.inc.php');

#Language setting for HTML area
# Only change this when translations exist in HTMLarea and plugin dirs
# (please send language files to HTMLarea development)

$nls['htmlarea']['el_GR'] = 'gr';
?>
