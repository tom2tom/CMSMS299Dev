<?php
#Bulgarian translation for CMS Made Simple
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

#bg Bulgarian
#Created by: Jordan Hlebarov <jd@jdbg.com>
#Maintained by: Jordan Hlebarov <jd@jdbg.com>

#Native language name
#NOTE: Enocde me with HTML escape chars like &#231; or &ntilde; so I work on every page
$nls['language']['bg_BG'] = 'Български';
$nls['englishlang']['bg_BG'] = 'Bulgarian';

#Possible aliases for language
$nls['alias']['bg'] = 'bg_BG';
$nls['alias']['bulgarian'] = 'bg_BG' ;
$nls['alias']['bul'] = 'bg_BG' ;

#Possible locale for language
$nls['locale']['bg_BG'] = 'bg_BG.utf8,bg_BG.utf-8,bg_BG.UTF-8,bg_BG,bulgarian,Bulgarian_Bulgaria.1251';

#Encoding of the language
$nls['encoding']['bg_BG'] = 'UTF-8';

#Location of the file(s)
$nls['file']['bg_BG'] = array(__DIR__.'/bg_BG/admin.inc.php');

#Language setting for HTML area
# Only change this when translations exist in HTMLarea and plugin dirs
# (please send language files to HTMLarea development)

$nls['htmlarea']['bg_BG'] = 'bg';
?>
