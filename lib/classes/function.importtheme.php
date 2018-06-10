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
//    $xmlfile = './CMS-AdminTheme.xml';
	 $xmlfile = '/root/Downloads/CMSMS-AdminTheme-Ghostgum.xml'; //DEBUG
}

libxml_use_internal_errors(true);
$xml = simplexml_load_file($xmlfile, 'SimpleXMLElement', LIBXML_NOCDATA);
if ($xml === false) {
    echo 'Failed to load file '.$xmlfile."\n";
    foreach (libxml_get_errors() as $error) {
        echo 'Line '.$error->line.': '.$error->message."\n";
    }
    libxml_clear_errors();
    return false;
}

$val = (string)$xml->dtdversion;
if (version_compare($val, CmsAdminThemeBase::THEME_DTD_MINVERSION) < 0) {
    echo 'Invalid file format';
    return false;
}

$themename = (string)$xml->name;
$all = CmsAdminThemeBase::GetAvailableThemes(true);
//$all = self::GetAvailableThemes(true); //DEBUG
if (isset($all[$themename])) {
	// theme is installed now
	include_once $all[$themename];
	$class = $themename.'Theme';
    $current = $class::THEME_VERSION ?? '0';
	$val = (string)$xml->version; //TODO validate this
    if ($current !== '0' && version_compare($val, $current) < 0) {
	    echo 'Incompatible theme version';
	    return false;
	}
	$basepath = dirname($all[$themename]);
    if (!recursive_delete($basepath)) {
        echo 'Failed to clear existing theme data';
        return false;
    }
} else {
	$basepath = cms_join_path(CMS_ADMIN_DIR, 'Themes', $themename);
	if (!mkdir($basepath, 0771, true)) {
        echo 'Failed to create directory for theme data';
        return false;
	}
}

foreach ($xml->children() as $typenode) {
    if ($typenode->count() > 0) {
        switch ($typenode->getName()) {
            case 'files':
                foreach ($typenode->children() as $node) {
                    $rel = (string)$node->relpath;
                    $fp = $basepath.DIRECTORY_SEPARATOR.strtr($rel, ['\\'=>DIRECTORY_SEPARATOR,'/'=>DIRECTORY_SEPARATOR]);
                    if ((string)$node->isdir) {
                        if (!(is_dir($fp) || @mkdir($fp, 0771, true))) {
                            //TODO handle error
                            //break 2;
                        }
                    } elseif ((string)$node->encoded) {
                        if (@file_put_contents($fp, base64_decode((string)$node->content)) === false) {
                            //TODO handle error
                        }
                    } elseif (@file_put_contents($fp, htmlspecialchars_decode((string)$node->content)) === false) {
                        //TODO handle error
                    }
                }
                break;
/* for future processing of in-database theme data (design, categories?, styles, templates)
            case 'designs':
                foreach ($typenode->children() as $node) {
                    $row = (array)$node;
                    //TODO c.f. site import
                }
                break;
            case 'stylesheets':
                foreach ($typenode->children() as $node) {
                    $row = (array)$node;
                    //TODO c.f. site import
                }
                break;
            case 'categories':
                foreach ($typenode->children() as $node) {
                    $row = (array)$node;
                    //TODO c.f. site import
                }
                break;
            case 'templates':
                foreach ($typenode->children() as $node) {
                    $row = (array)$node;
                    //TODO c.f. site import
                }
                break;
            case 'designstyles': //relations between styles and designs
                foreach ($typenode->children() as $node) {
                    $row = (array)$node;
                    //TODO c.f. site import
                }
                break;
            case 'designtemplates': //relations between templates and designs
                foreach ($typenode->children() as $node) {
                    $row = (array)$node;
                    //TODO c.f. site import
                }
                break;
            case 'categorytemplates': //relations between templates and categories
                foreach ($typenode->children() as $node) {
                    $row = (array)$node;
                    //TODO c.f. site import
                }
                break;
*/
        }
    }
}

return true;
