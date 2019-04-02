<?php
#Methods for interacting with template objects
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
use CMSMS\AdminUtils;
use CMSMS\Events;
use CMSMS\internal\global_cache;
use CMSMS\User;
use CMSMS\UserOperations;
use CmsSQLErrorException;
use const CMS_DB_PREFIX;
use function audit;
use function cmsms;
use function endswith;
use function file_put_contents;
use function get_userid;
use function munge_string_to_url;

/**
 * A class of static methods for dealing with CmsLayoutTemplate objects.
 * This class is for template administration, by DesignManager module
 * and the like. It is not used for runtime template retrieval.
 *
 * @since 2.3
 * @package CMS
 * @license GPL
 * @author Robert Campbell
 */
class TemplateOperations
{
   /**
    * @ignore
    */
    const TABLENAME = 'layout_templates';

   /**
    * @ignore
    */
    const ADDUSERSTABLE = 'layout_tpl_addusers';

    protected static $identifiers;

    /**
     * @ignore
     * @throws CmsInvalidDataException
     */
    protected static function resolve_user($a)
    {
        if( is_numeric($a) && $a >= 1 ) return (int)$a;
        if( is_string($a) ) {
            $a = trim($a);
            if ($a !== '') {
                $ops = cmsms()->GetUserOperations();
                $ob = $ops->LoadUserByUsername($a);
                if( $ob instanceof User ) return $ob->id;
            }
        }
        if( $a instanceof User ) return $a->id;
        throw new CmsInvalidDataException('Could not resolve '.$a.' to a user id');
    }

    /**
     * @ignore
     * @throws CmsInvalidDataException
     */
    protected static function resolve_template($a)
    {
        if( is_numeric($a) && $a >= 1 ) return (int)$a;
        if( is_string($a) ) {
            $a = trim($a);
            if ($a !== '') {
                if( !isset(self::$identifiers) ) {
                    $db = CmsApp::get_instance()->GetDb();
                    $sql = 'SELECT id,originator,name FROM '.CMS_DB_PREFIX.self::TABLENAME.' ORDER BY id';
                    self::$identifiers = $db->GetAssoc($sql);
                }
                $parts = explode('::',$a);
                if( $parts[1] ) {
                    foreach( self::$identifiers as $id => &$row ) {
                        if( strcasecmp($row['name'],$parts[1]) == 0 && $row['originator'] == $parts[0] ) {
                            unset($row);
                            return $id;
                        }
                    }
                }
                else {
                    foreach( self::$identifiers as $id => &$row ) {
                        //aka CmsLayoutTemplateType::CORE
                        if( strcasecmp($row['name'],$a) == 0 && ($row['originator'] == '__CORE__' || $row['originator'] == '') ) {
                            unset($row);
                            return $id;
                        }
                    }
                }
                unset($row);
            }
            else {
                $a = '<missing name>';
            }
        }
        throw new CmsInvalidDataException('Could not resolve '.$a.' to a template id');
    }

    /**
     * Get the template originator from $tpl data directly, or indirectly from the template-type data for $tpl
     *
     * @ignore
     * @param CmsLayoutTemplate $tpl
     * @return mixed string | null
     */
    protected static function get_originator($tpl)
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
    * Create a new populated template object
    * @internal
    *
    * @param array $props
    * @param mixed $editors optional array of id's | null
    * @param mixed $designs optional array of id's | null
    * @param mixed $cats    optional array of id's | null
    * @returns CmsLayoutTemplate
    */
    protected static function create_template(array $props, $editors = null, $designs = null, $cats = null) : CmsLayoutTemplate
    {
        $tpl = new CmsLayoutTemplate();

        if( $editors == null ) {
            $db = CmsApp::get_instance()->GetDb();
            $sql = 'SELECT user_id FROM '.CMS_DB_PREFIX.self::ADDUSERSTABLE.' WHERE tpl_id=? ORDER BY user_id';
            $editors = $db->GetCol($sql,[ $props['id'] ]);
        }
        elseif( is_numeric($editors) ) {
            $editors = [(int)$editors];
        }

        if( $designs == null ) {
            if( !isset($db) ) $db = CmsApp::get_instance()->GetDb();
            $sql = 'SELECT design_id FROM '.CMS_DB_PREFIX.'layout_design_tplassoc WHERE tpl_id=? ORDER BY design_id';
            $designs = $db->GetCol($sql,[ $props['id'] ]);
        }
        elseif( is_numeric($designs) ) {
            $editors = [(int)$designs];
        }

        if( $cats == null ) {
            if( !isset($db) ) $db = CmsApp::get_instance()->GetDb();
            $sql = 'SELECT category_id FROM '.CMS_DB_PREFIX.'layout_cat_tplassoc WHERE tpl_id=? ORDER BY category_id';
            $cats = $db->GetCol($sql,[ $props['id'] ]);
        }
        elseif( is_numeric($cats) ) {
            $cats = [(int)$cats];
        }

        $params = $props + [
            'editors' => $editors,
            'designs' => $designs,
            'categories' => $cats,
        ];
        $tpl->set_properties($params);
        return $tpl;
    }

    /**
     * Check whether template properties are valid
     *
     * @param CmsLayoutTemplate $tpl
     * @throws CmsInvalidDataException if any checked property is invalid
     */
    public static function validate_template(CmsLayoutTemplate $tpl)
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

    /**
     * Update the data for a template object in the database
     *
     * @internal
     * @param CmsLayoutTemplate $tpl
     * @returns CmsLayoutTemplate
     */
    protected static function update_template(CmsLayoutTemplate $tpl) : CmsLayoutTemplate
    {
        $db = CmsApp::get_instance()->GetDb();
        $sql = 'UPDATE '.CMS_DB_PREFIX.self::TABLENAME.' SET
originator=?,
name=?,
description=?,
type_id=?,
type_dflt=?,
owner_id=?,
listable=?,
contentfile=?
modified=?
WHERE id=?';
        $tplid = $tpl->get_id();
		$args = [ self::get_originator($tpl),
          $tpl->name,
          $tpl->description,
          $tpl->type_id,
          $tpl->type_dflt,
          $tpl->owner_id,
          $tpl->listable,
          $tpl->contentfile,
          time(),
          $tplid,
        ];
//      $dbr =
        $db->Execute($sql, $args);
//MySQL UPDATE results are never reliable        if( !$dbr ) throw new CmsSQLErrorException($db->sql.' -- '.$db->ErrorMsg());

        if( ($fp = $tpl->get_content_filename()) ) {
            file_put_contents($fp,$tpl->content,LOCK_EX);
		}
		else {
	        $sql = 'UPDATE '.CMS_DB_PREFIX.self::TABLENAME.' SET content=? WHERE id=?';
	        $db->Execute($sql,[$tpl->content,$tplid]);
        }

        if( $tpl->type_dflt ) {
            // if it's default for a type, unset default flag for all other templates of this type
            $sql = 'UPDATE '.CMS_DB_PREFIX.self::TABLENAME.' SET type_dflt = 0 WHERE type_id = ? AND type_dflt = 1 AND id != ?';
//          $dbr =
            $db->Execute($sql,[ $tpl->type_id,$tplid ]);
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

        global_cache::clear('LayoutTemplates');
        audit($tpl->get_id(),'CMSMS','Template '.$tpl->get_name().' Updated');
        return $tpl; //DODO what use ? event ?
    }

    /**
     * Insert the data for a template into the database
     *
     * @param CmsLayoutTemplate $tpl
     * @return CmsLayoutTemplate template object representing the inserted template
     */
    protected static function insert_template(CmsLayoutTemplate $tpl) : CmsLayoutTemplate
    {
        $now = time();
        $db = CmsApp::get_instance()->GetDb();
        $sql = 'INSERT INTO '.CMS_DB_PREFIX.self::TABLENAME.
' (originator,name,content,description,type_id,type_dflt,owner_id,listable,contentfile,created,modified)
VALUES (?,?,?,?,?,?,?,?,?,?,?)';
        $args = [ self::get_originator($tpl),
          $tpl->name,
          $tpl->content, // maybe changed to a filename
          $tpl->description,
          $tpl->type_id,
          $tpl->type_dflt,
          $tpl->owner_id,
          $tpl->listable,
          $tpl->contentfile,
          $now,$now
        ];
        $dbr = $db->Execute($sql,$args);
        if( !$dbr ) {
            throw new CmsSQLErrorException($db->sql.' --7 '.$db->ErrorMsg());
        }

        $tplid = $tpl->id = $db->Insert_ID();

		if( $tpl->contentfile ) {
			$fn = munge_string_to_url($tpl->name).'.'.$tplid.'.tpl';
			$sql = 'UPDATE '.CMS_DB_PREFIX.self::TABLENAME.' SET content=? WHERE id=?';
			$db->Execute($sql,[$fn,$tplid]);
			$tmp = $tpl->content;
			$tpl->content = $fn;
			$fp = $tpl->get_content_filename();
			file_put_contents($fp,$tmp,LOCK_EX);
		}

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

        global_cache::clear('LayoutTemplates');

        audit($new_id,'CMSMS','Template '.$tpl->get_name().' Created');
        // return a fresh instance of the object (e.g. to pass to event handlers ??)
        $row = $tpl->_get_array();
        $tpl = self::create_template($row,$editors,$designs,$categories);
        return $tpl;
    }

    /**
     * Save a template
     *
     * @param CmsLayoutTemplate $tpl The template object.
     */
    public static function save_template(CmsLayoutTemplate $tpl)
    {
        self::validate_template($tpl);

        if( $tpl->get_id() ) {
            Events::SendEvent('Core','EditTemplatePre',[ get_class($tpl) => &$tpl ]);
            $tpl = self::update_template($tpl); // $tpl might now be different, thanks to event handler
            Events::SendEvent('Core','EditTemplatePost',[ get_class($tpl) => &$tpl ]);
        }
        else {
            Events::SendEvent('Core','AddTemplatePre',[ get_class($tpl) => &$tpl ]);
            $tpl = self::insert_template($tpl);
            Events::SendEvent('Core','AddTemplatePost',[ get_class($tpl) => &$tpl ]);
        }
    }

    /**
     * Delete a template
     * This does not modify the template object itself.
     *
     * @param CmsLayoutTemplate $tpl
     */
    public static function delete_template(CmsLayoutTemplate $tpl)
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
        global_cache::clear('LayoutTemplates');
    }

    /**
     * Get a specific template
     *
     * @param mixed $a  The template id or name (possibly as originator::name) of the wanted template
     * @return mixed CmsLayoutTemplate|null
     * @throws CmsDataNotFoundException
     */
    public static function get_template($a)
    {
        $id = self::resolve_template($a);

        $db = CmsApp::get_instance()->GetDb();
        $sql = 'SELECT * FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE id=?';
        $row = $db->GetRow($sql,[ $id ]);
        if( $row ) return self::create_template($row);
    }

    /**
     * [Re]set all properties of a template, using properties from another one
     *
     * @since 2.3
     * @deprecated since 2.3 this enables the deprecated CmsLaoutTemplate::load() method
     * @param CmsLayoutTemplate $tpl The template to be updated
     * @param mixed $a  The id or name of the template from which to source the replacement properties
     * @throws CmsInvalidDataException
     * @throws CmsDataNotFoundException
     */
    public static function replicate_template(CmsLayoutTemplate $tpl, $a)
    {
        $src = self::get_template($a);
        $data = $src->get_properties();
        $tpl->set_properties($data);
    }

    protected static function filter_editors(int $id, array $all) : array
    {
        $out = [];
        foreach( $all as $row ) {
            if( $row['tpl_id'] == $id ) $out[] = $row['user_id'];
        }
        return $out;
    }

    protected static function filter_designs(int $id, array $all) : array
    {
        $out = [];
        foreach( $all as $row ) {
            if( $row['tpl_id'] == $id ) $out[] = $row['design_id'];
        }
        return $out;
    }

    protected static function filter_categories(int $id, array $all) : array
    {
        $out = [];
        foreach( $all as $row ) {
            if( $row['tpl_id'] == $id ) $out[] = $row['category_id'];
        }
        return $out;
    }

    /**
     * Get multiple templates
     *
     * @param array $list An array of integer template id's.
     * @param bool $deep Optionally load attached data. Default true.
     * @return mixed CmsLayoutTemplate[] | null
     */
    public static function get_bulk_templates(array $list,bool $deep = true)
    {
        if( !$list ) return;

        $out = [];
        $db = CmsApp::get_instance()->GetDb();
        $str = implode(',',$list);
        $sql = 'SELECT * FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE id IN ('.$str.')';
        $rows = $db->GetArray($sql);
        if( $rows ) {
            $sql = 'SELECT * FROM '.CMS_DB_PREFIX.self::ADDUSERSTABLE.' WHERE tpl_id IN ('.$str.') ORDER BY tpl_id';
            $alleditors = $db->GetArray($sql);

            $sql = 'SELECT * FROM '.CMS_DB_PREFIX.'layout_design_tplassoc WHERE tpl_id IN ('.$str.') ORDER BY tpl_id';
            $alldesigns = $db->GetArray($sql);

            $sql = 'SELECT * FROM '.CMS_DB_PREFIX.'layout_cat_tplassoc WHERE tpl_id IN ('.$str.') ORDER BY tpl_id';
            $allcategories = $db->GetArray($sql);

            // put it all together, into object(s)
            foreach( $rows as $row ) {
                $id = $row['id'];
                $editors = self::filter_editors($id,$alleditors);
                $designs = self::filter_designs($id,$alldesigns);
                $categories = self::filter_categories($id,$allcategories);
                $out[] = self::create_template($row,$editors,$designs,$categories);
            }
        }
        return $out;
    }

    /**
     * Get all templates owned by the specified user
     *
     * @param mixed $a user id (int) or user name (string)
     * @return mixed CmsLayoutTemplate[] | null
     * @throws CmsInvalidDataException
     */
    public static function get_owned_templates($a) : array
    {
        $id = self::resolve_user($a);
        if( $id <= 0 ) throw new CmsInvalidDataException('Invalid user specified to '.__METHOD__);

        $sql = new CmsLayoutTemplateQuery([ 'u'=>$id ]);
        $list = $sql->GetMatchedTemplateIds();
        if( $list ) {
            return self::get_bulk_templates($list);
        }
    }

    /**
     * Get all templates that the specified user owns or may otherwise edit
     *
     * @param mixed $a user id (int) or user name (string)
     * @return mixed CmsLayoutTemplate[] | null
     * @throws CmsInvalidDataException
     */
    public static function get_editable_templates($a)
    {
        $id = self::esolve_user($a);
        if( $id <= 0 ) throw new CmsInvalidDataException('Invalid user specified to '.__METHOD__);

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
        if( $tpl_list ) {
            $tpl_list = array_unique($tpl_list);
            return self::get_bulk_templates($tpl_list);
        }
    }

    /**
     * Get all templates of the specified type
     *
     * @param CmsLayoutTemplateType $type
     * @return mixed CmsLayoutTemplate[]|null
     * @throws CmsDataNotFoundException
     */
    public static function get_all_templates_by_type(CmsLayoutTemplateType $type)
    {
        $db = CmsApp::get_instance()->GetDb();
        $sql = 'SELECT id FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE type_id=?';
        $list = $db->GetCol($sql,[ $type->get_id() ]);
        if( $list ) {
            return self::get_bulk_templates($list);
        }
    }

    /**
     * Get the default template of the specified type
     *
     * @param mixed $a  a template-type name (like originator::name), or
     * a (numeric) template-type id, or a CmsLayoutTemplateType object
     * @return mixed CmsLayoutTemplate | null
     * @throws CmsInvalidDataException
     * @throws CmsDataNotFoundException
     */
    public static function get_default_template_by_type($a)
    {
        if( is_numeric($a) ) {
            $tid = (int)$a;
        }
        elseif( is_string($a) ) {
            $db = CmsApp::get_instance()->GetDb();
            $parts = explode('::',$a);
            if( count($parts) == 1 ) {
                $sql = 'SELECT id FROM '.CMS_DB_PREFIX."layout_tpl_type WHERE name=? AND (originator='__CORE__' OR originator='' OR originator IS NULL)";
                $tid = $db->GetOne($sql,[ $a ]);
            }
            else {
                $sql = 'SELECT id FROM '.CMS_DB_PREFIX.'layout_tpl_type WHERE name=? AND originator=?';
                $tid = $db->GetOne($sql,[ $parts[1],$parts[0] ]);
            }
        }
        elseif( $a instanceof CmsLayoutTemplateType ) {
            $tid = $a->get_id();
        }
        else {
            $tid = null;
        }

        if( !$tid ) throw new CmsInvalidDataException('Invalid data passed to '.__METHOD__);

        if( !isset($db) ) $db = CmsApp::get_instance()->GetDb();
        $sql = 'SELECT id FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE type_dflt=1 AND type_id=?';
        $id = $db->GetOne($sql,[ $tid ]);
        if( $id ) return self::get_template($id);
    }

//============= OTHER FORMER CmsLayoutTemplate METHODS ============

   /**
    * Get a new template of the specified type
    *
    * @param mixed $a  a template-type name (like originator::name), or
    * a (numeric) template-type id, or a CmsLayoutTemplateType object
    * @return CmsLayoutTemplate
    * @throws CmsInvalidDataException
    */
    public static function get_template_by_type($a)
    {
        if( is_int($a) || is_string($a) ) {
            $tt = CmsLayoutTemplateType::load($a);
            if( $tt ) return $tt->create_new_template();
        }
        elseif( $a instanceof CmsLayoutTemplateType ) {
            return $a->create_new_template();
        }

        throw new CmsInvalidDataException('Invalid data passed to '.__METHOD__);
    }

    /**
    * Generate a unique name for a template
    *
    * @param string $prototype A prototype template name
    * @param string $prefix An optional name-prefix. Default ''.
    * @return mixed string | null
    * @throws CmsInvalidDataException
     */
    public static function get_unique_template_name(string $prototype, string $prefix = '') : string
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
    * Perform an advanced database-query on templates
    *
    * @see CmsLayoutTemplateQuery
    * @param array $params
    * @return type
    */
    public static function template_query(array $params)
    {
        $sql = new CmsLayoutTemplateQuery($params);
        $out = self::get_bulk_templates($sql->GetMatchedTemplateIds());

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
    * Test whether the specified user can edit the specified template.
    * This is a convenience method that loads the template, and then tests
    * if the specified user has edit ability to it.
    *
    * @param mixed $a An integer template id, or a string template name
    * @param mixed $userid An integer user id, or a string user name, or null.
    *  If no userid is specified, the currently logged in userid is used
    * @return bool
    */
    public static function user_can_edit_template($a,$userid = null)
    {
        if( is_null($userid) ) $userid = get_userid();

        // get the template, and additional users etc
        $tpl = self::get_template($a);
        if( $tpl->get_owner_id() == $userid ) return true;

        // get the user groups
        $addt_users = $tpl->get_additional_editors();
        if( $addt_users ) {
            if( in_array($userid,$addt_users) ) return true;

            $grouplist = (new UserOperations())->GetMemberGroups();
            if( $grouplist ) {
                foreach( $addt_users as $id ) {
                    if( $id < 0 && in_array(-((int)$id),$grouplist) ) return true;
                }
            }
        }

        return false;
    }

   /**
    * Process a named template through smarty
    * @param string $name
    * @return string (unless fetch fails?)
    */
    public static function process_named_template(string $name)
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
    public static function process_default_template($t)
    {
        $smarty = CmsApp::get_instance()->GetSmarty();
        $tpl = self::get_default_template_by_type($t);
        return $smarty->fetch('cms_template:id='.$tpl->get_id());
    }
 } // class
