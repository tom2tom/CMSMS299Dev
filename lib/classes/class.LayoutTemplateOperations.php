<?php
#Tools for interacting with template objects and the database
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

use cms_cache_handler;
use CmsInvalidDataException;
use CmsLayoutCollection;
use CmsLayoutTemplate;
use CmsLayoutTemplateQuery;
use CmsLayoutTemplateType;
use CmsLogicException;
use CMSMS\Database\Connection;
use CMSMS\HookManager;
use CmsSQLErrorException;
use InvalidArgumentException;
use const CMS_DB_PREFIX;
use function audit;
use function cms_warning;
use function cmsms;

/**
 * A class that manages storage of CmsLayoutTemplate objects in the database.
 * This class also supports caching, and sending hooks at various levels.
 *
 * @since 2.3
 * @package CMS
 * @license GPL
 * @author Robert Campbell
 */
class LayoutTemplateOperations
{
    /**
     * Given a template name, generate an id.
     *
     * This method uses a cached mapping of template names to id.
     * If the item does not exist in the cache, then the cache item is built from the database.
     *
     * @param string $name The template name
     * @return int|null
     */
    protected function template_name_to_id(string $name)
    {
        $map = global_cache::get(__METHOD__,__CLASS__);
        if( !$map ) {
            $db = CmsApp::get_instance()->GetDb();
            $sql = 'SELECT id,name FROM '.CmsLayoutTemplate::TABLENAME.' ORDER BY name';
            $arr = $db->->GetArray($sql);
            $map = null;
            foreach( $arr as $row ) {
                $map[$row['name']] = (int) $row['id'];
            }
            global_cache::set(__METHOD__,$map,__CLASS__);
        }
        if( $map && isset($map[$name]) ) return $map[$name];
    }

    /**
     * Given a type id, return its type id
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
            $sql = 'SELECT id,type_id FROM '.CmsLayoutTemplate::TABLENAME.' WHERE type_dflt = 1';
            $arr = $db->->GetArray($sql);
            $map = [];
            foreach( $arr as $row ) {
                $map[$row['type_id']] = (int) $row['id'];
            }
            global_cache::set(__METHOD__,$map,__CLASS__);
        }
        if( $map && isset($map[$type_id]) ) return $map[$type_id];
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
     * Adds or overwrites a template into the cache
     *
     * @internal
     * @param CmsLayoutTemplate $tpl
     * @throws InvalidArgumentException
     */
    protected function set_template_cached(CmsLayoutTemplate $tpl)
    {
        if( !$tpl->get_id() ) throw new InvalidArgumentException('Cannot cache a template with no id');
        global_cache::set($tpl->get_id(),$tpl,__CLASS__);
        $idx = global_cache::get('cached_index',__CLASS__);
        if( !$idx ) $idx = [];
        $idx[] = $tpl->get_id();
        $idx = array_unique($idx);
        global_cache::set('cached_index',__CLASS__);
    }

    /**
     * Get an index of all of the cached templates.
     *
     * @internal
     * @return int[]|null
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
     * Generate a unique template name
     *
     * @param string $prototype The input prototype
     * @param string $prefix A prefix to apply to all output
     * @return string
     * @throws CmsInvalidDataException
     * @throws CmsLogicException
     */
    public function generate_unique_template_name(string $prototype, string $prefix = '')
    {
        if( !$prototype ) throw new CmsInvalidDataException('Prototype name cannot be empty');
        $db = CmsApp::get_instance()->GetDb();
        $query = 'SELECT id FROM '.CmsLayoutTemplate::TABLENAME.' WHERE name = ?';
        for( $i = 0; $i < 25; $i++ ) {
            $name = $prefix.$prototype;
            if( $i == 0 ) $name = $prototype;
            else $name = $prefix.$prototype.' '.$i;
            $tmp = $db->GetOne($query,[$name]);
            if( !$tmp ) return $name;
        }
        throw new CmsLogicException('Could not generate a template name for '.$prototype);
    }

    /**
     * Validate a template to ensure that it is suitable for storage.
     *
     * This method throws exceptions if validation cannot be assured.
     *
     * @param CmsLayoutTemplate $tpl
     */
    public function validate_template(CmsLayoutTemplate $tpl)
    {
        $tpl->validate();

        $db = CmsApp::get_instance()->GetDb();
        $tmp = null;
        if( $tpl->get_id() ) {
            // double check the name.
            $query = 'SELECT id FROM '.CmsLayoutTemplate::TABLENAME.' WHERE name = ? AND id != ?';
            $tmp = $db->GetOne($query,[$tpl->get_name(),$tpl->get_id()]);
        } else {
            // double check the name.
            $query = 'SELECT id FROM '.CmsLayoutTemplate::TABLENAME.' WHERE name = ?';
            $tmp = $db->GetOne($query, [$tpl->get_name()));
        }
        if( $tmp ) throw new CmsInvalidDataException('Template with the same name already exists.');
    }

    /**
     * Update the template object into the database.
     *
     * @internal
     * @param CmsLayoutTemplate $tpl
     * @returns CmsLayoutTemplate
     */
    protected function _update_template(CmsLayoutTemplate $tpl) : CmsLayoutTemplate
    {
        $this->validate_template($tpl);

        $db = CmsApp::get_instance()->GetDb();
        $query = 'UPDATE '.CmsLayoutTemplate::TABLENAME.'
              SET name = ?, content = ?, description = ?, type_id = ?, type_dflt = ?, category_id = ?, owner_id = ?, listable = ?, modified = ?
              WHERE id = ?';
        $dbr = $db->Execute($query,
        [
            $tpl->get_name(),$tpl->get_content(),$tpl->get_description(),
            $tpl->get_type_id(),$tpl->get_type_dflt(),$tpl->get_category_id(),
            $tpl->get_owner_id(),$tpl->get_listable(),time(),
            $tpl->get_id()
        ]);
        if( !$dbr ) throw new CmsSQLErrorException($db->sql.' -- '.$db->ErrorMsg());

        if( $tpl->get_type_dflt() ) {
            // if it's default for a type, unset default flag for all other records with this type
            $query = 'UPDATE '.CmsLayoutTemplate::TABLENAME.' SET type_dflt = 0 WHERE type_id = ? AND type_dflt = 1 AND id != ?';
            $dbr = $db->Execute($query,[$tpl->get_type_id(),$tpl->get_id()]);
            if( !$dbr ) throw new CmsSQLErrorException($db->sql.' -- '.$db->ErrorMsg());
        }

        $query = 'DELETE FROM '.CmsLayoutTemplate::ADDUSERSTABLE.' WHERE tpl_id = ?';
        $dbr = $db->Execute($query,[$tpl->get_id()]);
        if( !$dbr ) throw new CmsSQLErrorException($db->sql.' -- '.$db->ErrorMsg());

        $t = $tpl->get_additional_editors();
        if( is_array($t) && count($t) ) {
            $query = 'INSERT INTO '.CmsLayoutTemplate::ADDUSERSTABLE.' (tpl_id,user_id) VALUES(?,?)';
            foreach( $t as $one ) {
                $dbr = $db->Execute($query,[$tpl->get_id(),(int)$one]);
            }
        }

        $query = 'DELETE FROM '.CMS_DB_PREFIX.CmsLayoutCollection::TPLTABLE.' WHERE tpl_id = ?';
        $dbr = $db->Execute($query,[$tpl->get_id()]);
        if( !$dbr ) throw new CmsSQLErrorException($db->sql.' -- '.$db->ErrorMsg());
        $t = $tpl->get_designs();
        if( is_array($t) && count($t) ) {
            $query = 'INSERT INTO '.CMS_DB_PREFIX.CmsLayoutCollection::TPLTABLE.' (tpl_id,design_id) VALUES(?,?)';
            foreach( $t as $one ) {
                $dbr = $db->Execute($query,[$tpl->get_id(),(int)$one]);
            }
        }

        global_cache::clear(__CLASS__);
        audit($tpl->get_id(),'CMSMS','Template '.$tpl->get_name().' Updated');
        return $tpl;
    }

    /**
     * Insert a template into the database
     *
     * @param CmsLayoutTemplate $tpl
     * @return CmsLayoutTemplate
     */
    protected function _insert_template(CmsLayoutTemplate $tpl) : CmsLayoutTemplate
    {
        $this->validate_template($tpl);

        $db = CmsApp::get_instance()->GetDb();

        $query = 'INSERT INTO '.CmsLayoutTemplate::TABLENAME.'
              (name,content,description,type_id,type_dflt,category_id,owner_id,
               listable,created,modified) VALUES (?,?,?,?,?,?,?,?,?,?)';
        $dbr = $db->Execute($query, [
            $tpl->get_name(),
            $tpl->get_content(),
            $tpl->get_description(),
            $tpl->get_type_id(),
            $tpl->get_type_dflt(),
            $tpl->get_category_id(),
            $tpl->get_owner_id(),
            $tpl->get_listable(),
            time(),time()
        ]);
        if( !$dbr ) throw new CmsSQLErrorException($db->sql.' -- '.$db->ErrorMsg());
        $new_id = $db->Insert_ID();

        if( $tpl->get_type_dflt() ) {
            // if it's default for a type, unset default flag for all other records with this type
            $query = 'UPDATE '.CmsLayoutTemplate::TABLENAME.' SET type_dflt = 0 WHERE type_id = ? AND type_dflt = 1 AND id != ?';
            $dbr = $db->Execute($query,[ $tpl->get_type_id(), $new_id ]);
            if( !$dbr ) throw new CmsSQLErrorException($db->sql.' -- '.$db->ErrorMsg());
        }

        $t = $tpl->get_additional_editors();
        if( is_array($t) && count($t) ) {
            $query = 'INSERT INTO '.CmsLayoutTemplate::ADDUSERSTABLE.' (tpl_id,user_id) VALUES(?,?)';
            foreach( $t as $one ) {
                //$dbr =
                $db->Execute($query,[$new_id,(int)$one]);
            }
        }

        $t = $tpl->get_designs();
        if( is_array($t) && count($t) ) {
            $query = 'INSERT INTO '.$this->design_assoc_table_name().' (tpl_id,design_id) VALUES(?,?)';
            foreach( $t as $one ) {
                //$dbr =
                $db->Execute($query,[$new_id,(int)$one]);
            }
        }

        global_cache::clear(__CLASS__);
        $arr = $tpl->_get_array();
        $tpl = $tpl::_load_from_data($arr);
        audit($new_id,'CMSMS','Template '.$tpl->get_name().' Created');
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
        $ops = HookManager::get_instance();
        if( $tpl->get_id() ) {
            $ops->do_hook('Core::EditTemplatePre', [ get_class($tpl) => &$tpl ] );
            $tpl = $this->_update_template($tpl);
            $ops->do_hook('Core::EditTemplatePost', [ get_class($tpl) => &$tpl ] );
            return;
        }

        $ops->do_hook('Core::AddTemplatePre', [ get_class($tpl) => &$tpl ] );
        $tpl = $this->_insert_template($tpl);
        $ops->do_hook('Core::AddTemplatePost', [ get_class($tpl) => &$tpl ] );
    }

    /**
     * Delete a template from the database
     *
     * This method removes a template object from the database and any caches.
     * it does not modify the template object, so care must be taken with the id.
     *
     * @param CmsLayoutTemplate $tpl
     */
    public function delete_template(CmsLayoutTemplate $tpl)
    {
        if( !($id = $tpl->get_id()) ) return;

        $ops = HookManager::get_instance();
        $ops->do_hook('Core::DeleteTemplatePre', [ get_class($tpl) => &$tpl ] );
        $db = CmsApp::get_instance()->GetDb();
        $query = 'DELETE FROM '.CMS_DB_PREFIX.CmsLayoutCollection::TPLTABLE.' WHERE tpl_id = ?';
        //$dbr =
        $db->Execute($query, [$id]);

        $query = 'DELETE FROM '.CmsLayoutTemplate::TABLENAME.' WHERE id = ?';
        //$dbr =
        $db->Execute($query, [$id]);

        @unlink($tpl->get_content_filename());

        audit($id,'CMSMS','Template '.$tpl->get_name().' Deleted');
        $ops->do_hook('Core::DeleteTemplatePost', [ get_class($tpl) => &$tpl ] );
        global_cache::clear(__CLASS__);
    }

    /**
     * Load a template
     *
     * @param mixed $a  The template id or name to load.
     * @return CmsLayoutTemplate|null
     */
    public function load_template($a)
    {
        $id = null;
        if( is_numeric($a) && $a > 0 ) {
            $id = $a;
        }
        else if( is_string($a) && strlen($a) > 0 ) {
            $id = $this->template_name_to_id($a);
            if( !$id ) {
                cms_warning('could not find a template id for template named '.$a);
                return;
            }
        }

        // if it exists in the cache, then we're done
        $obj = $this->get_cached_template($id);
        if( $obj ) return $obj;

        // load it from the database
        $db = CmsApp::get_instance()->GetDb();
        $sql = 'SELECT * FROM '.CmsLayoutTemplate::TABLENAME.' WHERE id = ?';
        $row = $db->GetRow($sql, [$id]);
        if( !$row ) return; // not found

        $sql = 'SELECT * FROM '.$this->design_assoc_table_name().' WHERE tpl_id = ?';
        $designs = $db->GetArray($sql, [$id]);

        $sql = 'SELECT * FROM '.CmsLayoutTemplate::ADDUSERSTABLE.' WHERE tpl_id = ?';
        $editors = $db->GetArray($sql, [$id]);

        // put it in the cache
        $obj = CmsLayoutTemplate::_load_from_data($row, $designs, $editors);
        $this->set_template_cached($obj);
        return $obj;
    }

    /**
     * Load multiple templates
     *
     * @param array $list An array of integer template ids.
     * @return CmsLayoutTemplate[] | null
     */
    public function load_bulk_templates(array $list)
    {
        if( !is_array($list) || count($list) == 0 ) return;

        $get_assoc_designs = function(int $id, array $alldesigns) {
            $out = null;
            foreach( $alldesigns as $design ) {
                if( $design['tpl_id'] < $id ) continue;
                if( $design['tpl_id'] > $id ) continue;
                $out[] = $design['design_id'];
            }
            return $out;
        };

        $get_assoc_users = function(int $id, array $allusers) {
            $out = null;
            foreach( $allusers as $user ) {
                if( $user['tpl_id'] < $id ) continue;
                if( $user['tpl_id'] > $id ) continue;
                $out[] = $user['user_id'];
            }
            return $out;
        };

        $list2 = array_diff($list,$this->get_cached_templates());
        if( is_array($list2) && count($list2) > 0 ) {
            // have to load these items and put them in the cache.
            $db = CmsApp::get_instance()->GetDb();
            $str = implode(',',$list2);
            $sql = 'SELECT * FROM '.CmsLayoutTemplate::TABLENAME." WHERE id IN ({$str})";
            $rows = $db->GetArray($sql);
            if( $rows ) {
                $sql = 'SELECT * FROM '.$this->design_assoc_table_name().' WHERE tpl_id IN ('.$str.') ORDER BY tpl_id';
                $alldesigns = $db->GetArray($sql);

                $sql = 'SELECT * FROM '.CmsLayoutTemplate::ADDUSERSTABLE.' WHERE tpl_id IN ('.$str.') ORDER BY tpl_id';
                $allusers = $db->GetArray($sql);

                // put it all together, create an object
                foreach( $rows as $row ) {
                    $id = $row['id'];
                    $obj = CmsLayoutTemplate::_load_from_data($row,$get_assoc_designs($id,$alldesigns),$get_assoc_users($id,$allusers));
                    // put it in the cache, we'll get it in a bit.
                    $this->set_template_cached($obj);
                }
                // cache it
            }
        }

        // read from the cache
        $out = null;
        foreach( $list as $tpl_id ) {
            $out[] = $this->get_cached_template($tpl_id);
        }
        return $out;
    }

    /**
     * Load all templates of a given type
     *
     * @param CmsLayoutTemplateType $type
     * @return CmsLayoutTemplate[]
     */
    public function load_templates_by_type(CmsLayoutTemplateType $type)
    {
        // get the template type id => template_id list
        // see if we have this map in the cache
        $map = null;
        $key = 'types_to_tpl_'.$type->get_id();
        if( global_cache::exists($key,__CLASS__) ) {
            $map = global_cache::get($key,__CLASS__);
        } else {
            $db = CmsApp::get_instance()->GetDb();
            $sql = 'SELECT id FROM '.CmsLayoutTemplate::TABLENAME.' WHERE id = ?';
            $list = $db->GetCol($sql,$type->get_id());
            if( is_array($list) && !empty($list) ) {
                $map = $list;
                global_cache::set($key,$list,__CLASS__);
            }
        }

        if( $map ) return $this->load_bulk_templates($map);
    }

    /**
     * Get all of the templates owned by a specific user
     *
     * @param mixed $a Either the integer uid or the username of a user.
     * @return CmsLayoutTemplate[]
     * @throws CmsInvalidDataException
     */
    public function get_owned_templates($a)
    {
        $n = $this->_resolve_user($a);
        if( $n <= 0 ) throw new CmsInvalidDataException('Invalid user specified to get_owned_templates');

        $query = new CmsLayoutTemplateQuery(['u'=>$n]);
        $tmp = $query->GetMatchedTemplateIds();
        return $this->load_bulk_templates($tmp);
    }

    /**
     * Get all of the templates that a user owns or can otherwise edit.
     *
     * @param mixed $a Either the integer uid or a username
     * @param CmsLayoutTemplate[]
     * @return type
     * @throws CmsInvalidDataException
     */
    public function get_editable_templates($a)
    {
        $n = $this->_resolve_user($a);
        if( $n <= 0 ) throw new CmsInvalidDataException('Invalid user specified to get_owned_templates');
        $db = CmsApp::get_instance()->GetDb();

        $sql = 'SELECT id FROM '.CmsLayoutTemplate::TABLENAME;
        $parms = $where = null;
        if( !cmsms()->GetUserOperations()->CheckPermission($n,'Modify Templates') ) {
            $sql .= ' WHERE owner_id = ?';
            $parms[] = $n;
        }
        $list = $db->GetCol($sql, $parms);
        if( !$list ) $list = [];

        $sql = 'SELECT tpl_id  FROM '.CmsLayoutTemplate::ADDUSERSTABLE.' WHERE user_id = ?';
        $list2 = $db->GetCol($sql, [$n]);
        if( !$list2 ) $list2 = [];

        $tpl_list = array_merge($list,$list2);
        $tpl_list = array_unique($tpl_list);
        if( !count($tpl_list) ) return;

        return $this->load_bulk_templates($tpl_list);
    }

    /**
     * Given a template type, get all templates
     *
     * @param CmsLayoutTemplateType $type
     * @return CmsLayoutTemplate[]|null
     */
    public function load_all_templates_by_type(CmsLayoutTemplateType $type)
    {
        $sql = 'SELECT id FROM '.CmsLayoutTemplate::TABLENAME.' WHERE type_id = ?';
        $tmp = $db->GetCol($sql, [ $type->get_id() ] );
        if( $tmp ) return $this->load_bulk_templates($tmp);
    }

    /**
     * Given a template type, get the default template of that type
     *
     * @param mixed $t A type name, a type id, or a CmsLayoutTemplateType object
     * @return type
     * @throws CmsInvalidDataException
     */
    public function load_default_template_by_type($t)
    {
        $t2 = null;
        if( is_int($t) || is_string($t) ) {
            // todo: this should be a method in this, or another manager class.
            $t2 = CmsLayoutTemplateType::load($t);
        }
        else if( $t instanceof CmsLayoutTemplateType ) {
            $t2 = $t;
        }

        if( !$t2 ) throw new CmsInvalidDataException('Invalid data passed to CmsLayoutTemplate::;load_dflt_by_type()');

        // search our preloaded template first
        $tpl_id = $this->get_default_template_by_type($t2->get_id());
        if( $tpl_id ) return $this->load_template($tpl_id);
    }

    /**
     * @ignore
     */
    protected function design_assoc_table_name() : string
    {
        return CMS_DB_PREFIX.'layout_design_tplassoc'; //aka CmsLayoutCollection::TPLTABLE
    }
} // class
