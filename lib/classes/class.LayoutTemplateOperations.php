<?php
#Methods for interacting with template objects and the database
#Copyright (C) 2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

namespace CMSMS;

use CmsApp;
use CmsDataNotFoundException;
use CmsInvalidDataException;
use CmsLayoutCollection;
use CmsLayoutTemplate;
use CmsLayoutTemplateCategory;
use CmsLayoutTemplateQuery;
use CmsLayoutTemplateType;
use CmsLogicException;
use CMSMS\AdminUtils;
use CMSMS\Events;
use CMSMS\internal\global_cache;
use CMSMS\User;
use CMSMS\UserOperations;
use CmsSQLErrorException;
use InvalidArgumentException;
use const CMS_DB_PREFIX;
use function audit;
use function cms_warning;
use function cmsms;
use function endswith;
use function get_userid;

/**
 * A class that manages storage of CmsLayoutTemplate objects in the database.
 * This class also supports caching, and sending events at various levels.
 *
 * @since 2.3
 * @package CMS
 * @license GPL
 * @author Robert Campbell
 */
class LayoutTemplateOperations
{
   /**
    * @ignore
    */
    const TABLENAME = 'layout_templates';

   /**
    * @ignore
    */
    const ADDUSERSTABLE = 'layout_tpl_addusers';

    /**
     * Given a template name, return the corresponding id
     *
     * This method uses a cached mapping of template names to id's.
     * If the item does not exist in the cache, then the cache is built from the database.
     *
     * @param string $name The template name
     * @return int|null
     */
    protected function template_name_to_id(string $name)
    {
        $map = global_cache::get(__METHOD__,__CLASS__);
        if( !$map ) {
            $db = CmsApp::get_instance()->GetDb();
            $sql = 'SELECT name,id FROM '.CMS_DB_PREFIX.self::TABLENAME.' ORDER BY name';
            $map = $db->GetAssoc($sql);
            global_cache::set(__METHOD__,$map,__CLASS__);
        }
        if( $map ) return $map[$name] ?? null;
    }

    /**
     * Given a template-type id, return the corresponding identifier (id)
     *
     * This method uses a cache that is built from the database if necessary.
     *
     * @param int $type_id
     * @return int|null The default template id, if any.
     */
    protected function get_default_template_by_type(int $type_id)
    {
        $map = global_cache::get(__METHOD__,__CLASS__);
        if( !$map ) {
            $db = CmsApp::get_instance()->GetDb();
            $sql = 'SELECT type_id,id FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE type_dflt = 1 ORDER BY type_id';
            $map = $db->GetAssoc($sql);
            global_cache::set(__METHOD__,$map,__CLASS__);
        }
        if( $map ) return $map[$type_id] ?? null;
    }

    /**
     * Test if a template exists in the cache, by its id.
     *
     * @internal
     * @param int $tpl_id The template id
     * @return CmsLayoutTemplate|null
     */
    protected function get_cached_template(int $tpl_id)
    {
        return global_cache::get($tpl_id,__CLASS__);
    }

    /**
     * Adds a template to, or overwrites a template in, the cache
     *
     * @internal
     * @param CmsLayoutTemplate $tpl
     * @throws InvalidArgumentException
     */
    protected function set_template_cached(CmsLayoutTemplate $tpl)
    {
        if( !($id = $tpl->get_id()) ) throw new InvalidArgumentException('Cannot cache a template with no id');
        global_cache::set($id,$tpl,__CLASS__);

        $idx = global_cache::get('cached_index',__CLASS__);
        if( !$idx ) $idx = [];
        $idx[] = $id;
        $idx = array_unique($idx);
        global_cache::set('cached_index',$idx,__CLASS__);
    }

    /**
     * Get a list of id's of all the cached templates.
     *
     * @internal
     * @return int[] maybe empty
     */
    protected function get_cached_templates()
    {
        $idx = global_cache::get('cached_index',__CLASS__);
        if( !$idx ) $idx = [];
        return $idx;
    }

    /**
     * @ignore
     */
    protected function _resolve_user($a)
    {
        if( is_numeric($a) && $a > 0 ) return $a;
        if( is_string($a) && strlen($a) ) {
            $ops = cmsms()->GetUserOperations();
            $ob = $ops->LoadUserByUsername($a);
            if( $ob instanceof User ) return $ob->id;
        }
        if( $a instanceof User ) return $a->id;
        throw new CmsLogicException('Could not resolve '.$a.' to a user id');
    }

    /**
    * Generate a unique name for a template
    *
    * @param string $prototype A prototype template name
    * @param string $prefix An optional name prefix.
    * @return mixed string | null
    * @throws CmsInvalidDataException
    * @throws CmsLogicException
     */
    public static function generate_unique_template_name(string $prototype, string $prefix = '') : string
    {
        if( !$prototype ) throw new CmsInvalidDataException('Prototype name cannot be empty');

        $db = CmsApp::get_instance()->GetDb();
        $sql = 'SELECT name FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE name LIKE %?%';
		$all = $db->GetCol($sql,[ $prototype ]);
		if( $all ) {
			$name = $prototype;
			$i = 0;
			while( in_array($name, $all) ) {
				$name = $prefix.$prototype.'_'.++$i;
			}
			return $name;
		}
		return $prototype;
    }

    /**
     * Check template properties are valid
     *
     * @param CmsLayoutTemplate $tpl
     * @throws CmsInvalidDataException if any checked property is invalid
     */
    public function validate_template(CmsLayoutTemplate $tpl)
    {
        $name = $tpl->get_name();
        if( !$name ) throw new CmsInvalidDataException('Each template must have a name');
        if( endswith($name,'.tpl') ) throw new CmsInvalidDataException('Invalid name for a database template');
        if( !AdminUtils::is_valid_itemname($name) ) {
            throw new CmsInvalidDataException('There are invalid characters in the template name');
        }

        if( !$tpl->get_content() ) throw new CmsInvalidDataException('Each template must have some content');
        if( $tpl->get_type_id() <= 0 ) throw new CmsInvalidDataException('Each template must be associated with a type');

        $db = CmsApp::get_instance()->GetDb();
        $tmp = null;
        if( $tpl->get_id() ) {
            // double check the name.
            $sql = 'SELECT id FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE name = ? AND id != ?';
            $tmp = $db->GetOne($sql,[ $tpl->get_name(),$tpl->get_id() ]);
        } else {
            // double check the name.
            $sql = 'SELECT id FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE name = ?';
            $tmp = $db->GetOne($sql,[ $tpl->get_name() ]);
        }
        if( $tmp ) throw new CmsInvalidDataException('Template with the same name already exists.');
    }

    protected function _get_anyowner($tpl)
    {
        $tmp = $tpl->get_originator();
        if( $tmp ) {
            return $tmp;
        }
        $id = $tpl->get_type_id();
        if( $id > 0 ) {
            $db = CmsApp::get_instance()->GetDb();
            $sql = 'SELECT originator FROM '.CMS_DB_PREFIX.CmsLayoutTemplateType::TABLENAME.' WHERE id = ?';
            $dbr = $db->GetOne($sql,[ $id ]);
            if( $dbr ) {
                return $dbr;
            }
        }
        return null;
    }

    /**
     * Update the data for a template object in the database.
     *
     * @internal
     * @param CmsLayoutTemplate $tpl
     * @returns CmsLayoutTemplate
     */
    protected function _update_template(CmsLayoutTemplate $tpl) : CmsLayoutTemplate
    {
        $this->validate_template($tpl);

        $db = CmsApp::get_instance()->GetDb();
        $now = time();
        $tplid = $tpl->get_id();

        $sql = 'UPDATE '.CMS_DB_PREFIX.self::TABLENAME.' SET
originator=?,
name=?,
content=?,
description=?,
type_id=?,
type_dflt=?,
owner_id=?,
listable=?,
isfile=?
modified=?
WHERE id=?';
//      $dbr =
        $db->Execute($sql,
        [ $this->_get_anyowner($tpl),
         $tpl->get_name(),
         $tpl->get_content(),
         $tpl->get_description(),
         $tpl->get_type_id(),
         $tpl->get_type_dflt(),
         $tpl->get_owner_id(),
         $tpl->get_listable(),
         $tpl->get_content_file(),
         $now,$tplid
        ]);
//MySQL UPDATE results are never reliable        if( !$dbr ) throw new CmsSQLErrorException($db->sql.' -- '.$db->ErrorMsg());

        if( $tpl->get_type_dflt() ) {
            // if it's default for a type, unset default flag for all other templates of this type
            $sql = 'UPDATE '.CMS_DB_PREFIX.self::TABLENAME.' SET type_dflt = 0 WHERE type_id = ? AND type_dflt = 1 AND id != ?';
//          $dbr =
            $db->Execute($sql,[ $tpl->get_type_id(),$tplid ]);
//          if( !$dbr ) throw new CmsSQLErrorException($db->sql.' -- '.$db->ErrorMsg());
        }

        $sql = 'DELETE FROM '.CMS_DB_PREFIX.self::ADDUSERSTABLE.' WHERE tpl_id = ?';
        $dbr = $db->Execute($sql,[ $tplid ]);
        if( !$dbr ) throw new CmsSQLErrorException($db->sql.' --5 '.$db->ErrorMsg());

        $t = $tpl->get_additional_editors();
        if( $t ) {
            $sql = 'INSERT INTO '.CMS_DB_PREFIX.self::ADDUSERSTABLE.' (tpl_id,user_id) VALUES(?,?)';
            foreach( $t as $id ) {
                $db->Execute($sql,[ $tplid,(int)$id ]);
            }
        }

        $sql = 'DELETE FROM '.CMS_DB_PREFIX.CmsLayoutCollection::TPLTABLE.' WHERE tpl_id = ?';
        $dbr = $db->Execute($sql,[ $tplid ]);
        if( !$dbr ) throw new CmsSQLErrorException($db->sql.' --6 '.$db->ErrorMsg());
        $t = $tpl->get_designs();
        if( $t ) {
            $stmt = $db->Prepare('INSERT INTO '.CMS_DB_PREFIX.CmsLayoutCollection::TPLTABLE.' (design_id,tpl_id,tpl_order) VALUES(?,?,?)');
            $i = 1;
            foreach( $t as $id ) {
                $db->Execute($stmt,[ (int)$id,$tplid,$i ]);
                ++$i;
            }
        }

        $sql = 'DELETE FROM '.CMS_DB_PREFIX.CmsLayoutTemplateCategory::TPLTABLE.' WHERE tpl_id = ?';
        $db->Execute($sql,[ $tplid ]);
        $t = $tpl->get_categories();
        if( $t ) {
            $stmt = $db->Prepare('INSERT INTO '.CMS_DB_PREFIX.CmsLayoutTemplateCategory::TPLTABLE.' (category_id,tpl_id,tpl_order) VALUES(?,?,?)');
            $i = 1;
            foreach( $t as $id ) {
                $db->Execute($stmt,[ (int)$id,$tplid,$i ]);
                ++$i;
            }
        }

        global_cache::clear(__CLASS__);
        audit($tpl->get_id(),'CMSMS','Template '.$tpl->get_name().' Updated');
        return $tpl; //DODO what use ? event ?
    }

    /**
     * Insert the data for a template into the database
     *
     * @param CmsLayoutTemplate $tpl
     * @return CmsLayoutTemplate template object representing the inserted template
     */
    protected function _insert_template(CmsLayoutTemplate $tpl) : CmsLayoutTemplate
    {
        $this->validate_template($tpl);

        $db = CmsApp::get_instance()->GetDb();
        $now = time();

        $sql = 'INSERT INTO '.CMS_DB_PREFIX.self::TABLENAME.
' (originator,name,content,description,type_id,type_dflt,owner_id,listable,isfile,created,modified)
VALUES (?,?,?,?,?,?,?,?,?,?,?)';
        $db = CmsApp::get_instance()->GetDb();
        $dbr = $db->Execute($sql,
        [ $this->_get_anyowner($tpl),
         $tpl->get_name(),
         $tpl->get_content(), // if file ??
         $tpl->get_description(),
         $tpl->get_type_id(),
         $tpl->get_type_dflt(),
         $tpl->get_owner_id(),
         $tpl->get_listable(),
         $tpl->get_content_file(),
         $now,$now
        ]);
        if( !$dbr ) {
            throw new CmsSQLErrorException($db->sql.' --7 '.$db->ErrorMsg());
        }

        $tplid = $tpl->id = $db->Insert_ID();

        if( $tpl->get_type_dflt() ) {
            // if it's default for a type, unset default flag for all other records with this type
            $sql = 'UPDATE '.CMS_DB_PREFIX.self::TABLENAME.' SET type_dflt = 0 WHERE type_id = ? AND type_dflt = 1 AND id != ?';
//          $dbr =
            $db->Execute($sql,[ $tpl->get_type_id(),$tplid ]);
//          if( !$dbr ) throw new CmsSQLErrorException($db->sql.' -- '.$db->ErrorMsg());
        }

        $editors = $tpl->get_additional_editors();
        if( $editors ) {
            $sql = 'INSERT INTO '.CMS_DB_PREFIX.self::ADDUSERSTABLE.' (tpl_id,user_id) VALUES(?,?)';
            foreach( $editors as $id ) {
                //TODO use statement
                $db->Execute($sql,[ $tplid,(int)$id ]);
            }
        }

        $designs = $tpl->get_designs();
        if( $designs ) {
            $sql = 'INSERT INTO '.CMS_DB_PREFIX.CmsLayoutCollection::TPLTABLE.' (design_id,tpl_id,tpl_order) VALUES(?,?,?)';
            $i = 1;
            foreach( $designs as $id ) {
                $db->Execute($sql,[ (int)$id,$tplid,$i ]);
                ++$i;
            }
        }

        $categories = $tpl->get_categories();
        if( $categories ) {
            $sql = 'INSERT INTO '.CMS_DB_PREFIX.CmsLayoutTemplateCategory::TPLTABLE.' (category_id,tpl_id,tpl_order) VALUES(?,?,?)';
            $i = 1;
            foreach( $categories as $id ) {
                $db->Execute($sql,[ (int)$id,$tplid,$i ]);
                ++$i;
            }
        }

        global_cache::clear(__CLASS__);

        audit($new_id,'CMSMS','Template '.$tpl->get_name().' Created');
        // return a fresh instance of the object, to pass to event handlers
        $row = $tpl->_get_array();
        $tpl = $this->_load_from_data($row,$designs,$editors,$categories);
        return $tpl;
    }

    /**
     * Save a template into the database.
     *
     * This method takes an existing template object and either updates it into the database, or inserts it.
     *
     * @param CmsLayoutTemplate $tpl The template object.
     */
    public function save_template(CmsLayoutTemplate $tpl)
    {
        if( $tpl->get_id() ) {
            Events::SendEvent('Core','EditTemplatePre',[ get_class($tpl) => &$tpl ]);
            $tpl = $this->_update_template($tpl);
            Events::SendEvent('Core','EditTemplatePost',[ get_class($tpl) => &$tpl ]);
            return;
        }

        Events::SendEvent('Core','AddTemplatePre',[ get_class($tpl) => &$tpl ]);
        $tpl = $this->_insert_template($tpl);
        Events::SendEvent('Core','AddTemplatePost',[ get_class($tpl) => &$tpl ]);
    }

    /**
     * Remove a template object from the database and any caches.
     * This does not modify the template object, so care must be taken with the id.
     *
     * @param CmsLayoutTemplate $tpl
     */
    public function delete_template(CmsLayoutTemplate $tpl)
    {
        if( !($id = $tpl->get_id()) ) return;

        Events::SendEvent('Core','DeleteTemplatePre',[ get_class($tpl) => &$tpl ]);
        $db = CmsApp::get_instance()->GetDb();
        $sql = 'DELETE FROM '.CMS_DB_PREFIX.CmsLayoutCollection::TPLTABLE.' WHERE tpl_id = ?';
        //$dbr =
        $db->Execute($sql,[ $id ]);

        $sql = 'DELETE FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE id = ?';
        //$dbr =
        $db->Execute($sql,[ $id ]);

        @unlink($tpl->get_content_filename());

        audit($id,'CMSMS','Template '.$tpl->get_name().' Deleted');
        Events::SendEvent('Core','DeleteTemplatePost',[ get_class($tpl) => &$tpl ]);
        global_cache::clear(__CLASS__);
    }

   /**
    * Create a new template object and record it in the caches
    * @internal
    * @param
    * @param
    * @param
    * @param
    * @returns CmsLayoutTemplate
    */
    protected function _load_from_data(array $row, array $design_list = [], array $editors_list = [], array $cats_list = []) : CmsLayoutTemplate
    {
        $tpl = new CmsLayoutTemplate();
        $tpl->_data = $row; //TODO impossible
        $fn = $tpl->get_content_filename(); //CHECKME path
        if( is_file($fn) && is_readable($fn) ) {
            $tpl->content = file_get_contents($fn);
            $tpl->modified = filemtime($fn);
        }
        $tpl->_TODO = $editors_list; //TODO impossible
        $tpl->_in_designs = $design_list;
        $tpl->_TODO = $cats_list;

        self::$_obj_cache[$tpl->id] = $tpl;
        self::$_name_cache[$tpl->name] = $tpl->id; //TODO  USE  template_name_to_id(string $name)
        return $tpl;
    }


/*
EDITORS
        if( is_null($this->_addt_editors) ) {
            if( $this->get_id() ) {
                $db = CmsApp::get_instance()->GetDb();
                $sql = 'SELECT user_id FROM '.CMS_DB_PREFIX.LayoutTemplateOperations::ADDUSERSTABLE.' WHERE tpl_id = ?';
                $col = $db->GetCol($sql,[ $this->get_id() ]);
                if( $col ) $this->_addt_editors = $col;
                else $this->_addt_editors = [];
            }
        }

CATS
        if( !is_array($this->_cat_assoc) ) {
            if( !$this->get_id() ) return [];
            $db = CmsApp::get_instance()->GetDb();
            $sql = 'SELECT category_id FROM '.CMS_DB_PREFIX.CmsLayoutTemplateCategory::TPLTABLE.' WHERE tpl_id = ? ORDER BY tpl_order';
            $tmp = $db->GetCol($sql,[ (int)$this->get_id() ]);
            if( $tmp ) $this->_cat_assoc = $tmp;
            else $this->_cat_assoc = [];
        }

DESIGNS
        if( !is_array($this->_design_assoc) ) {
            if( !$this->get_id() ) return [];
            $db = CmsApp::get_instance()->GetDb();
            $sql = 'SELECT design_id FROM '.CMS_DB_PREFIX.CmsLayoutCollection::TPLTABLE.' WHERE tpl_id = ?';
            $tmp = $db->GetCol($sql,[ (int)$this->get_id() ]);
            if( $tmp ) $this->_design_assoc = $tmp;
            else $this->_design_assoc = [];

        }
*/
    /**
     * [Re]set all properties of an existing template object, using values from another template
     *
     * @deprecated since 2.3
     * @param CmsLayoutTemplate $tpl The template to be updated
     * @param mixed $a  The id or name of the template from which to source the replacement properties
     * @return bool indicating success
     */
    public function populate_template(CmsLayoutTemplate $tpl, $a)
    {
        return false;
    }

    /**
     * Load a specific template
     *
     * @param mixed $a  The template id or name of the wanted template
     * @return mixed CmsLayoutTemplate|null
     * @throws CmsDataNotFoundException
     */
    public static function load_template($a)
    {
        $id = null;
        if( is_numeric($a) && $a > 0 ) {
            $id = $a;
        }
        else if( is_string($a) && strlen($a) > 0 ) {
            $id = $this->template_name_to_id($a);
            if( !$id ) {
                cms_warning('Could not find a template identified as '.$a);
                return;
            }
        }

        // if it exists in the cache, then we're done
        $tpl = $this->get_cached_template($id);
        if( $tpl ) return $tpl;

        // load it from the database
        $db = CmsApp::get_instance()->GetDb();
        $sql = 'SELECT * FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE id = ?';
        $row = $db->GetRow($sql,[ $id ]);
        if( !$row ) return; // not found

        $sql = 'SELECT * FROM '.$this->design_assoc_table_name().' WHERE tpl_id = ?';
        $designs = $db->GetArray($sql,[ $id ]);

        $sql = 'SELECT * FROM '.CMS_DB_PREFIX.self::ADDUSERSTABLE.' WHERE tpl_id = ?';
        $editors = $db->GetArray($sql,[ $id ]);

        // put it in the cache
        $tpl = $this->_load_from_data($row,$designs,$editors,$categories); //WANT categories
        $this->set_template_cached($tpl);
        return $tpl;
/* IMPORT
        $db = CmsApp::get_instance()->GetDb();
        $row = null;
        if( is_numeric($a) && $a > 0 ) {
            if( isset(self::$_obj_cache[$a]) ) return self::$_obj_cache[$a];
            $sql = 'SELECT * FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE id = ?';
            $row = $db->GetRow($sql,[ (int)$a ]);
        }
        else if( is_string($a) && $a !== '' ) {
            if( template_name_to_id(string $name)             isset(self::$_name_cache[$a]) ) {
                $id = $this->template_name_to_id($a);
                return self::$_obj_cache[$id];
            }

            $sql = 'SELECT * FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE name = ?';
            $row = $db->GetRow($sql,[ $a ]);
        }
        if( $row ) {
            //todo OTHER ARRAYS
            return $this->_load_from_data($row,$designs,$editors,$categories); //WANT designs editors categories
        }
        throw new CmsDataNotFoundException('Could not find template identified by '.$a);
*/
    }

    protected function get_assoc_designs(int $id, array $alldesigns)
    {
        $out = [];
        foreach( $alldesigns as $design ) {
            if( $design['tpl_id'] == $id ) $out[] = $design['design_id'];
        }
        return $out;
    }

    protected function get_assoc_users(int $id, array $allusers)
    {
        $out = [];
        foreach( $allusers as $user ) {
            if( $user['tpl_id'] == $id ) $out[] = $user['user_id'];
        }
        return $out;
    }

    protected function get_assoc_categories(int $id, array $allcategories)
    {
        $out = [];
        foreach( $allcategories as $cat ) {
            if( $cat['TODO'] == $id ) $out[] = $cat['TODO'];
        }
        return $out;
    }

    /**
     * Load multiple templates
     *
     * @param array $list An array of integer template ids.
     * @param bool $deep Optionally load attached data. Default true.
     * @return mixed CmsLayoutTemplate[] | null
     */
    public static function load_bulk_templates(array $list,bool $deep = true)
    {
        if( !$list ) return;

        $list2 = array_diff($list,$this->get_cached_templates());
        if( $list2 ) {
            // have to load these items and put them in the cache.
            $db = CmsApp::get_instance()->GetDb();
            $str = implode(',',$list2);
            $sql = 'SELECT * FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE id IN ('.$str.')';
            $rows = $db->GetArray($sql);
            if( $rows ) {
                $sql = 'SELECT * FROM '.$this->design_assoc_table_name().' WHERE tpl_id IN ('.$str.') ORDER BY tpl_id';
                $alldesigns = $db->GetArray($sql);

                $sql = 'SELECT * FROM '.CMS_DB_PREFIX.self::ADDUSERSTABLE.' WHERE tpl_id IN ('.$str.') ORDER BY tpl_id';
                $allusers = $db->GetArray($sql);

                $sql = $TODO; // 'SELECT * FROM '.CMS_DB_PREFIX.self::ADDUSERSTABLE.' WHERE tpl_id IN ('.$str.') ORDER BY tpl_id';
                $allcategories = $db->GetArray($sql);

                // put it all together, create an object
                foreach( $rows as $row ) {
                    $id = $row['id'];
                    $designs = $this->get_assoc_designs($id,$alldesigns);
                    $editors = $this->get_assoc_users($id,$allusers);
                    $categories = $this->get_assoc_categories($id,$allcategories);
                    $tpl = $this->_load_from_data($row,$designs,$editors,$categories);
                    // put it in the cache
                    $this->set_template_cached($tpl);
                }
            }
        }

        // read back from the cache
        $out = null;
        foreach( $list as $tpl_id ) {
            $out[] = $this->get_cached_template($tpl_id);
        }
        return $out;
/* IMPORT
        $list2 = [];
        foreach( $list as $id ) {
            if( !is_numeric($id) ) continue;
            $id = (int)$id;
            if( $id < 1 ) continue;
            if( isset(self::$_obj_cache[$id]) ) continue;
            $list2[] = $id;
        }
        $list2 = array_unique($list2,SORT_NUMERIC);

        if( $list2 ) {
            // get the data and populate the cache.
            $db = CmsApp::get_instance()->GetDb();
            $designs_by_tpl = [];

            if( $deep ) {
                foreach( $list2 as $id ) {
                    $designs_by_tpl[$id] = [];
                }
                $dquery = 'SELECT tpl_id,design_id FROM '.CMS_DB_PREFIX.CmsLayoutCollection::TPLTABLE.
                 ' WHERE tpl_id IN ('.implode(',',$list2).') ORDER BY tpl_id,tpl_order';
                $designs_tmp1 = $db->GetArray($dquery);
                foreach( $designs_tmp1 as $row ) {
                    $designs_by_tpl[$row['tpl_id']][] = $row['design_id'];
                }
            }

            $sql = 'SELECT * FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE id IN ('.implode(',',$list2).')';
            $dbr = $db->GetArray($sql);
            if( $dbr ) {
                foreach( $dbr as $row ) {
                    //$tpl =
                    //$designs = $this->designs_by_tpl[$row['id']])
                    $this->_load_from_data($row,$designs,$editors,$categories); //CRAP also designs, editors, categories
                }
            }
        }

        // pull what we can from the cache
        $out = [];
        foreach( $list as $id ) {
            if( !is_numeric($id) ) continue;
            $id = (int)$id;
            if( $id > 0 && isset(self::$_obj_cache[$id]) ) $out[] = self::$_obj_cache[$id];
        }
*/
    }

    /**
     * Load all templates of a given type
     *
     * @param CmsLayoutTemplateType $type
     * @return mixed CmsLayoutTemplate[] | null
     */
    public function load_templates_by_type(CmsLayoutTemplateType $type)
    {
        // get the template type id => template_id list
        // see if we have this map in the cache
        $map = null;
        $key = 'types_to_tpl_'.$type->get_id();
        if( global_cache::exists($key,__CLASS__) ) {
            $map = global_cache::get($key,__CLASS__);
        }
        else {
            $db = CmsApp::get_instance()->GetDb();
            $sql = 'SELECT id FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE id = ?';
            $list = $db->GetCol($sql,$type->get_id());
            if( is_array($list) && !empty($list) ) {
                $map = $list;
                global_cache::set($key,$list,__CLASS__);
            }
        }

        if( $map ) return self::load_bulk_templates($map);
    }

    /**
     * Get all of the templates owned by a specific user
     *
     * @param mixed $a Either the integer uid or the username of a user.
     * @return CmsLayoutTemplate[]
     * @throws CmsInvalidDataException
     */
    public static function get_owned_templates($a) : array
    {
        $id = $this->_resolve_user($a);
        if( $id <= 0 ) throw new CmsInvalidDataException('Invalid user specified to get_owned_templates');

        $sql = new CmsLayoutTemplateQuery([ 'u'=>$id ]);
        $tmp = $sql->GetMatchedTemplateIds();
        return self::load_bulk_templates($tmp);
    }

    /**
     * Get all of the templates that a user owns or may otherwise edit.
     *
     * @param mixed $a Either the integer uid or a username
     * @return mixed CmsLayoutTemplate[] | null
     * @throws CmsInvalidDataException
     */
    public static function get_editable_templates($a)
    {
        $id = $this->_resolve_user($a);
        if( $id <= 0 ) throw new CmsInvalidDataException('Invalid user specified to get_owned_templates');

        $db = CmsApp::get_instance()->GetDb();
        $sql = 'SELECT id FROM '.CMS_DB_PREFIX.self::TABLENAME;
        $parms = $where = [];
        if( !cmsms()->GetUserOperations()->CheckPermission($id,'Modify Templates') ) {
            $sql .= ' WHERE owner_id = ?';
            $parms[] = $id;
        }
        $list = $db->GetCol($sql, $parms);
        if( !$list ) $list = [];

        $sql = 'SELECT tpl_id  FROM '.CMS_DB_PREFIX.self::ADDUSERSTABLE.' WHERE user_id = ?';
        $list2 = $db->GetCol($sql,[ $id ]);
        if( !$list2 ) $list2 = [];

        $tpl_list = array_merge($list,$list2);
        $tpl_list = array_unique($tpl_list);
        if( $tpl_list ) return self::load_bulk_templates($tpl_list);
    }

    /**
     * Given a template type, get all templates
     *
     * @param CmsLayoutTemplateType $type
     * @return mixed CmsLayoutTemplate[]|null
     */
    public function load_all_templates_by_type(CmsLayoutTemplateType $type)
    {
        $sql = 'SELECT id FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE type_id = ?';
        $tmp = $db->GetCol($sql,[ $type->get_id() ]);
        if( $tmp ) return self::load_bulk_templates($tmp);
    }

    /**
     * Load the default template of a specified type
     *
     * @param mixed $t A type name, a type id, or a CmsLayoutTemplateType object
     * @return mixed CmsLayoutTemplate | null
     * @throws CmsInvalidDataException
     * @throws CmsDataNotFoundException
     */
    public function load_default_template_by_type($t)
    {
        if( is_int($t) || is_string($t) ) {
            $t2 = self::load_template($t);
        }
        else if( $t instanceof CmsLayoutTemplateType ) {
            $t2 = $t;
        }
        else {
            $t2 = null;
        }

        if( !$t2 ) throw new CmsInvalidDataException('Invalid data passed to '.__METHOD__);

        $tpl_id = $this->get_default_template_by_type($t2->get_id());
        if( $tpl_id ) return self::load_template($tpl_id);
    }

    /**
     * @ignore
     */
    protected function design_assoc_table_name() : string
    {
        return CMS_DB_PREFIX.'layout_design_tplassoc'; //aka CmsLayoutCollection::TPLTABLE
    }

//============= FORMER CmsLayoutTemplate METHODS ============

   /**
    * Perform an advanced database-query on templates
    *
    * @see CmsLayoutTemplateQuery
    * @param array $params
    * @return type
    */
    public static function template_query(array $params)
    {
        $sql = new CmsLayoutTemplateQuery($params);
        $out = self::load_bulk_templates($sql->GetMatchedTemplateIds());

        if( isset($params['as_list']) && count($out) ) {
            $tmp2 = [];
            foreach( $out as $tpl ) {
                $tmp2[$tpl->get_id()] = $tpl->get_name();
            }
            return $tmp2;
        }
        return $out;
    }

   /**
    * Get the id's of all loaded/cached templates
    *
    * @return array of integer template ids, maybe empty
    */
    public static function get_loaded_templates()
    {
        if( !empty(self::$_obj_cache) ) {
            return array_keys(self::$_obj_cache);
        }
        return [];
    }

   /**
    * Test whether the user specified can edit the specified template
    * This is a convenience method that loads the template, and then tests
    * if the specified user has edit ability to it.
    *
    * @param mixed $a An integer template id, or a string template name
    * @param mixed $userid An integer user id, or a string user name, or null.
    *  If no userid is specified the currently logged in userid is used
    * @return bool
    */
    public static function user_can_edit($a,$userid = null)
    {
        if( is_null($userid) ) $userid = get_userid();

        // get the template, and additional users etc
        $tpl = self::load_template($a);
        if( $tpl->get_owner_id() == $userid ) return true;

        // get the user groups
        $addt_users = $tpl->get_additional_editors();
        if( $addt_users ) {
            if( in_array($userid,$addt_users) ) return true;

            $grouplist = UserOperations::get_instance()->GetMemberGroups();
            if( $grouplist ) {
                foreach( $addt_users as $id ) {
                    if( $id < 0 && in_array(-((int)$id),$grouplist) ) return true;
                }
            }
        }

        return false;
    }

   /**
    * Create a new template of the specific type
    *
    * @param mixed $t A CmsLayoutTemplateType object, an integer template type id, or a string template type identifier
    * @return CmsLayoutTemplate
    * @throws CmsInvalidDataException
    */
    public static function &create_by_type($t)
    {
        $t2 = null;
        if( is_int($t) || is_string($t) ) {
            $t2 = CmsLayoutTemplateType::load($t);
        }
        else if( $t instanceof CmsLayoutTemplateType ) {
            $t2 = $t;
        }

        if( !$t2 ) throw new CmsInvalidDataException('Invalid data passed to '.__METHOD__);

        return $t2->create_new_template();
    }

   /**
    * Load all templates of a specific type
    *
    * @throws CmsDataNotFoundException
    * @param CmsLayoutTemplateType $type
    * @return mixed array of CmsLayoutTemplate objects, or null
    */
    public static function load_all_by_type(CmsLayoutTemplateType $type)
    {
        $db = CmsApp::get_instance()->GetDb();
        $sql = 'SELECT * FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE type_id = ?';
        $tmp = $db->GetArray($sql,[ $type->get_id() ]);
        if( !$tmp ) return;

        $out = [];
        foreach( $tmp as $row ) {
            $out[] = $this->_load_from_data($row,$designs,$editors,$categories); // WANT designs editors categories
        }
        return $out;
    }

   /**
    * Process a named template through smarty
    * @param string $name
    * @return string (unless fetch fails?)
    */
    public static function process_by_name(string $name)
    {
        $smarty = CmsApp::get_instance()->GetSmarty();
        return $smarty->fetch('cms_template:name='.$name);
    }

   /**
    * Process the default template of a specified type
    *
    * @param mixed $t A CmsLayoutTemplateType object, an integer template type id, or a string template type identifier
    * @return string
    */
    public static function process_dflt($t)
    {
        $smarty = CmsApp::get_instance()->GetSmarty();
        $tpl = self::load_default_template_by_type($t);
        return $smarty->fetch('cms_template:id='.$tpl->get_id());
    }
 } // class
