<?php
#procedure to import an admin theme, replacing any existing theme of the same name
#Copyright (C) 2018 The CMSMS Dev Team <coreteam@cmsmadesimple.org>
#This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>
#
#This program is free software; you can redistribute it and/or modify
#it under the terms of the GNU General Public License as published by
#the Free Software Foundation; either version 2 of the License, or
#(at your option) any later version.
#
#This program is distributed in the hope that it will be useful,
#but WITHOUT ANY WARRANTY; without even the implied warranty of
#MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#GNU General Public License for more details.
#You should have received a copy of the GNU General Public License
#along with this program. If not, see <https://www.gnu.org/licenses/>.

if (empty($xmlfile)) {
    //source filepath not named by includer-script
    $xmlfile = './CMS-AdminTheme.xml';
}

libxml_use_internal_errors(true);
$xml = simplexml_load_file($xmlfile);
if ($xml === false) {
    echo 'Failed to load file '.$xmlfile."\n";
    foreach (libxml_get_errors() as $error) {
        echo 'Line '.$error->line.': '.$error->message."\n";
    }
    libxml_clear_errors();
    return false;
}

$themename = $xml->xpath('/theme/name');
$basepath = cms_join_path(CMS_ADMIN_DIR, 'Themes', $themename);
if (isdir($basepath)) {
    if (!recursive_delete($basepath)) {
        echo 'Failed to clear existing theme data';
        return false;
    }
}
mkdir($basepath, 0771, true);
//TODO handle error

foreach ($xml->children() as $typenode) {
    if ($typenode->count() > 0) {
        switch ($typenode->getName()) {
            case 'files':
                foreach ($typenode->children() as $node) {
                    $row = (array)$node;
                    $fp = $basepath.DIRECTORY_SEPARATOR.str_replace(['\\','/'], [DIRECTORY_SEPARATOR,DIRECTORY_SEPARATOR], $row['relpath']);
                    if (!empty($row['isdir'])) {
                        if (!@mkdir($fp, 0771, true)) {
                            //TODO handle error
                            //break 2;
                        }
                    } elseif (!empty($row['encoded'])) {
                        @file_put_contents($fp, base64_decode($row['content']));
                    } else {
                        @file_put_contents($fp, htmlspecialchars_decode($row['content']));
                    }
                }
                break;
            case 'designs':
                foreach ($typenode->children() as $node) {
                    $row = (array)$node;
                    //TODO
                }
                break;
            case 'stylesheets':
                foreach ($typenode->children() as $node) {
                    $row = (array)$node;
                    //TODO
                }
                break;
            case 'categories':
                foreach ($typenode->children() as $node) {
                    $row = (array)$node;
                    //TODO
                }
                break;
            case 'templates':
                foreach ($typenode->children() as $node) {
                    $row = (array)$node;
                    //TODO
                }
                break;
            case 'designstyles': //relations between styles and designs
                foreach ($typenode->children() as $node) {
                    $row = (array)$node;
                    //TODO
                }
                break;
            case 'designtemplates': //relations between templates and designs
                foreach ($typenode->children() as $node) {
                    $row = (array)$node;
                    //TODO
                }
                break;
            case 'categorytemplates': //relations between templates and categories
                foreach ($typenode->children() as $node) {
                    $row = (array)$node;
                    //TODO
                }
                break;
        }
    }
}

return true;
