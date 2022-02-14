<?php
#US English translation for CMS Made Simple
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

#Ideas from Gallery's implementation made to CMSMS by Ted Kulp

#US English
#Created by: Ted Kulp <tedkulp@users.sf.net>
#Maintained by: Ted Kulp <tedkulp@users.sf.net>
#This is the default language

#Native language name
#NOTE: Enocde me with HTML escape chars like &#231; or &ntilde; so I work on every page
$nls['language']['en_US'] = 'English';
$nls['englishlang']['en_US'] = 'English';

#Possible aliases for language
$nls['alias']['en'] = 'en_US';
$nls['alias']['english'] = 'en_US' ;
$nls['alias']['eng'] = 'en_US' ;
$nls['alias']['en-US'] = 'en_US';
$nls['alias']['en_CA'] = 'en_US' ;
$nls['alias']['en_GB'] = 'en_US' ;
$nls['alias']['en_US.ISO8859-1'] = 'en_US' ;

#Encoding of the language
$nls['encoding']['en_US'] = 'UTF-8';

#Location of the file(s)
$nls['file']['en_US'] = array(__DIR__.'/en_US/admin.inc.php');

#Language setting for HTML area
# Only change this when translations exist in HTMLarea and plugin dirs
# (please send language files to HTMLarea development)

$nls['htmlarea']['en_US'] = 'en';
?>
