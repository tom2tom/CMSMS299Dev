<?php
/*
Fast and small class supporting generation of frontend pages.
Copyright (C) 2019-2023 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

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

use CMSMS\CapabilityType;
use CMSMS\Lone;
use Exception;
use RuntimeException;
use const CMS_DB_PREFIX;

/**
 * This is a speed- and size-optimized alternative to
 *  ContentOperations::LoadContentFromId() and ...FromAlias()
 * for getting page-content objects during a request.
 *
 * @package CMS
 * @license GPL
 * @since 3.0
 */
class PageLoader
{
    // static properties here >> Lone property|ies ?
    /**
     * Cache for content-object(s) loaded during the current request.
     * Might be > 1 page e.g. for cross-referencing.
     * Each object is stored by id and by alias.
     * @ignore
     */
    protected static $_loaded = [];

    /**
     * Map: type => object-class for non-static content types
     * @ignore
     */
    protected static $_xtype_classes;

    /**
     * Whether _xtype_classes has been populated
     * @ignore
     */
    protected static $_xtype_loaded = false;

    /**
     * Retrieve and cache data for non-core page-object-classes
     */
    protected static function poll_xclasses()
    {
        $list = Lone::get('LoadedMetadata')->get('capable_modules', false, CapabilityType::CONTENT_TYPES);
        if ($list) {
            $ops = Lone::get('ModuleOperations');
            foreach ($list as $modname) {
                $obj = $ops->get_module_instance($modname); // should register stuff for newly-loaded modules
                $obj = null; // help the garbage-collector
            }
        }
        $ops = Lone::get('ContentTypeOperations');
        $list = $ops->content_types;
        if ($list) {
            foreach ($list as $obj) { // $obj = ContentType
                if (!isset(self::$_xtype_classes[$obj->type])) {
                    self::$_xtype_classes[$obj->type] = $obj->class; //TODO if filepath instead of class
                }
            }
        }
    }

    /**
     * Return a frontend-display adapted page-content object, sourced from database.
     * This constructs and populates a CMSMS\contenttypes\whatever object, or
     * some_module\noncore_content_class object.
     *
     * Effectively this is a cut-down alternative to
     *  ContentOperations::LoadContentFromId(deep) and ...FromAlias
     * It is NOT for content-tree processing, or ContentManager page listing or editing.
     *
     * @param mixed $a page identifier: id (int|numeric string) or alias (other string)
     * @return mixed ContentBase-derived object | null
     * @throws Exception if wanted content-type is N/A
     */
    public static function LoadContent($a)
    {
        $contentobj = self::$_loaded[$a] ?? null;
        if (!$contentobj) {
            $db = Lone::get('Db');
            $pref = CMS_DB_PREFIX;
            $sql = <<<EOS
SELECT C.*,T.displayclass FROM {$pref}content C
LEFT JOIN {$pref}content_types T on C.type=T.`name`
WHERE (content_id=? OR content_alias=?) AND active!=0
EOS;
            $row = $db->getRow($sql, [$a, $a]);
            if ($row) {
                if ($row['displayclass']) {
                    $classname = $row['displayclass'];
                } elseif (!self::$_xtype_loaded) {
                    self::$_xtype_classes = [];
                    self::poll_xclasses();
                    self::$_xtype_loaded = true;
                    $classname = self::$_xtype_classes[$row['type']] ?? '';
                }
                if ($classname) {
                    if (is_file($classname)) {
                        require_once $classname;
                        $classname = substr(basename($classname), 6, -4);
                    }
                    unset($row['displayclass']);
                    $contentobj = new $classname($row);
                    self::$_loaded[(int)$row['content_id']] = $contentobj;
                    self::$_loaded[$row['content_alias']] = &$contentobj;
                } else {
                    throw new RuntimeException('Unrecognized content type \''.$row['type'].'\' in '.__METHOD__);
                }
            }
        }
        return $contentobj;
    }

    /**
     * Identify and include the file defining the specified content type.
     * Not merely an autoloader-bypass, as types may instantiated by modules.
     *
     * @param mixed CmsContentType | ContentType $obj
     * @return mixed ContentType | null
     */
    public static function LoadContentType($obj)
    {
        //MAYBE use trait to cut down footprint
        return Lone::get('ContentTypeOperations')->LoadContentType($obj);
    }
}
