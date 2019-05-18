<?php
/*
Fast and small class supporting generation of frontend pages.
Copyright (C) 2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

use cms_utils;
use CmsApp;
use CmsCoreCapabilities;
use Exception;
use const CMS_DB_PREFIX;
use const CMS_ROOT_PATH;
use function cms_join_path;

/**
 * This is ...
 *
 * @package CMS
 * @license GPL
 * @since 2.3
 */
class PageLoader
{
    /**
     * Cache for content-object(s) loaded during the current request.
     * May be > 1 e.g. for cross-referencing
     * @ignore
     */
    protected static $_loaded = [];

    /**
     * @ignore
     */
    protected static $_loaded_names = [];

    /**
     * @ignore
     */
    protected static $_class_names = null;

    /**
     * Cache all known content-classes
	 * This is a cut-down alternative to ContentOperations::_get_content_types()
     */
    protected static function poll_classes()
    {
        $patn = cms_join_path(CMS_ROOT_PATH, 'lib', 'classes', 'contenttypes', 'class.*.php');
        $list = glob($patn, GLOB_NOSORT);
        foreach ($list as $fp) {
            $class = substr(basename($fp), 6, -4);
            $lc = strtolower($class);
            if ($lc != 'contentbase') {
                self::$_class_names[$lc] = 'CMSMS\\contenttypes\\'.$class;
            }
        }

        //TODO c.f. ContentOperations::register_content_type - handler classes registered THERE by loading modules
        $list = (new ModuleOperations())->get_modules_with_capability(CmsCoreCapabilities::CONTENT_TYPES);
        foreach ($list as $modname) {
            cms_utils::get_module($modname);
        }
        //TODO GET DATA TO self::$_class_names
    }

    /**
     * Return a frontend-display adapted page-data object, sourced from local cache or database.
     * This constructs and populates a CMSMS\contenttypes\whatever object, and
     * caches its properties locally. For the page being displayed, the object will
     * be retrieved several times, to process different sections of its content.
     * This is NOT for content-tree processing, or ContentManager page listing or editing.
	 * Effectively a cut-down alternative to ContentOperations::LoadContentFromId & ...FromAlias
     *
     * @param mixed $a page identifier: id (int|numeric string) or alias (other string)
     * @return mixed ContentBase object | null  TODO CONTENTPAGE
     */
    public static function LoadContent($a)
    {
        if (is_numeric($a)) {
            $contentobj = self::$_loaded[$a] ?? null;
        } else {
            $contentobj = null;
        }
        if (!$contentobj) {
            $id = self::$_loaded_names[$a] ?? false;
            if ($id) {
                $contentobj = self::$_loaded[$id];
            } else {
                if (self::$_class_names === null) {
                    self::poll_classes();
                }
                $db = CmsApp::get_instance()->GetDb();
                $sql = 'SELECT * FROM '.CMS_DB_PREFIX.'content WHERE (content_id=? OR content_alias=?) AND active!=0';
                $row = $db->GetRow($sql, [ $a,$a ]);
                if ($row) {
                    $lc = strtolower($row['type']);
                    $classname = self::$_class_names[$lc] ?? '';
                    if (!$classname) {
                        throw new Exception('Unrecognized content type: '.$row['type'].' in '.__METHOD__);
                    }
                    $contentobj = new $classname($row);
                    $id = (int)$row['content_id'];
                    self::$_loaded[$id] = $contentobj;
                    self::$_loaded_names[$row['content_alias']] = $id;
                }
            }
        }

        return $contentobj;
    }

    /**
     * Identify and include the file defining the specified content type.
     * Not merely an autoloader-bypass, as types may instantiated by modules.
     *
     * @param mixed CmsContentType | CmsContentTypePlaceholder $obj
	 * @return mixed ContentTypePlaceHolder | null
     */
    public static function LoadContentType($obj)
    {
		//TODO use trait to cut down footprint
        return ContentOperations::get_instance()->LoadContentType($obj);
    }
}
