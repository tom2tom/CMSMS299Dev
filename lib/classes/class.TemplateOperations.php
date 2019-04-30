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

use cms_config;
use CmsApp;
use CmsDataNotFoundException;
use CmsInvalidDataException;
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
use function check_permission;
use function cms_join_path;
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

    /**
     * @ignore
     */
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
                        if( strcasecmp($row['name'],$a) == 0 && ($row['originator'] == CmsLayoutTemplate::CORE || $row['originator'] == '') ) {
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
    * @param mixed $groups    optional array of id's | null
    * @returns CmsLayoutTemplate
    */
    protected static function create_template(array $props, $editors = null, $groups = null) : CmsLayoutTemplate
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

        if( $groups == null ) {
            if( !isset($db) ) $db = CmsApp::get_instance()->GetDb();
            // table aka CmsLayoutTemplateCategory::MEMBERSTABLE
            $sql = 'SELECT DISTINCT group_id FROM '.CMS_DB_PREFIX.CmsLayoutTemplateCategory::MEMBERSTABLE.' WHERE tpl_id=? ORDER BY group_id';
            $groups = $db->GetCol($sql,[ $props['id'] ]);
        }
        elseif( is_numeric($groups) ) {
            $groups = [(int)$groups];
        }

        $params = $props + [
            'editors' => $editors,
            'groups' => $groups,
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
/*
        $sql = 'DELETE FROM '.CMS_DB_PREFIX.CmsLayoutCollection::TPLTABLE.' WHERE tpl_id = ?'; DISABLED
        $dbr = $db->Execute($sql,[ $tplid ]);
        if( !$dbr ) throw new CmsSQLErrorException($db->sql.' --6 '.$db->ErrorMsg());
        $t = $tpl->get_designs(); DISABLED
        if( $t ) {
            $stmt = $db->Prepare('INSERT INTO '.CMS_DB_PREFIX.CmsLayoutCollection::TPLTABLE.' (design_id,tpl_id,tpl_order) VALUES(?,?,?)'); DISABLED
            $i = 1;
            foreach( $t as $id ) {
                $db->Execute($stmt,[ (int)$id,$tplid,$i ]);
                ++$i;
            }
            $stmt->close();
        }
*/
        $sql = 'DELETE FROM '.CMS_DB_PREFIX.CmsLayoutTemplateCategory::MEMBERSTABLE.' WHERE tpl_id = ?';
        $db->Execute($sql,[ $tplid ]);
        $t = $tpl->get_groups();
        if( $t ) {
            $stmt = $db->Prepare('INSERT INTO '.CMS_DB_PREFIX.CmsLayoutTemplateCategory::MEMBERSTABLE.' (group_id,tpl_id,item_order) VALUES(?,?,?)');
            $i = 1;
            foreach( $t as $id ) {
                $db->Execute($stmt,[ (int)$id,$tplid,$i ]);
                ++$i;
            }
            $stmt->close();
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
        $sql = 'INSERT INTO '.CMS_DB_PREFIX.self::TABLENAME.'
(originator,name,content,description,type_id,type_dflt,owner_id,listable,contentfile)
VALUES (?,?,?,?,?,?,?,?,?)';
        $args = [ self::get_originator($tpl),
          $tpl->name,
          $tpl->content, // maybe changed to a filename
          $tpl->description,
          $tpl->type_id,
          $tpl->type_dflt,
          $tpl->owner_id,
          $tpl->listable,
          $tpl->contentfile,
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

/*        $designs = $tpl->get_designs();
        if( $designs ) {
            $sql = 'SELECT MAX(tpl_order) AS v FROM '.CMS_DB_PREFIX.CmsLayoutCollection::TPLTABLE.' WHERE design_id=?'; DISABLED
            $sql2 = 'INSERT INTO '.CMS_DB_PREFIX.CmsLayoutCollection::TPLTABLE.' (design_id,tpl_id,tpl_order) VALUES(?,?,?)';
            foreach( $designs as $id ) {
                $mid = (int)$db->GetOne($sql,[ $id ]);
                $db->Execute($sql2,[ (int)$id,$tplid,$mid + 1 ]);
            }
        }
*/
        $groups = $tpl->get_groups();
        if( $groups ) {
            $sql = 'INSERT INTO '.CMS_DB_PREFIX.CmsLayoutTemplateCategory::MEMBERSTABLE.' (group_id,tpl_id,item_order) VALUES(?,?,?)';
            $i = 1;
            foreach( $groups as $id ) {
                $db->Execute($sql,[ (int)$id,$tplid,$i ]);
                ++$i;
            }
        }

        global_cache::clear('LayoutTemplates');

        audit($tplid,'CMSMS','Template '.$tpl->get_name().' Created');
        // return a fresh instance of the object (e.g. to pass to event handlers ??)
        $row = $tpl->get_properties();
        $tpl = self::create_template($row,$editors,$groups);
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
     * This does not modify the template object itself, nor any pages which use the template.
     *
     * @param CmsLayoutTemplate $tpl
     */
    public static function delete_template(CmsLayoutTemplate $tpl)
    {
        if( !($id = $tpl->get_id()) ) return;

        Events::SendEvent('Core','DeleteTemplatePre',[ get_class($tpl) => &$tpl ]);
        $db = CmsApp::get_instance()->GetDb();
/*        $sql = 'DELETE FROM '.CMS_DB_PREFIX.CmsLayoutCollection::TPLTABLE.' WHERE tpl_id = ?';  DISABLED
        //$dbr =
        $db->Execute($sql,[ $id ]);
*/
        $sql = 'DELETE FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE id = ?';
        //$dbr =
        $db->Execute($sql,[ $id ]);

        @unlink($tpl->get_content_filename()); //TODO if relevant

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

    protected static function filter_groups(int $id, array $all) : array
    {
        $out = [];
        foreach( $all as $row ) {
            if( $row['tpl_id'] == $id ) $out[] = $row['group_id'];
        }
        return $out;
    }

    /**
     * Get multiple templates
     *
     * @param array $list Integer template id(s)
     * @param bool $deep Optional flag whether to load attached data. Default true.
     * @return array CmsLayoutTemplate object(s) or empty
     */
    public static function get_bulk_templates(array $list, bool $deep = true) : array
    {
        if( !$list ) return [];

        $out = [];
        $db = CmsApp::get_instance()->GetDb();
        $str = implode(',',$list);
        $sql = 'SELECT * FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE id IN ('.$str.')';
        $rows = $db->GetArray($sql);
        if( $rows ) {
            $sql = 'SELECT * FROM '.CMS_DB_PREFIX.self::ADDUSERSTABLE.' WHERE tpl_id IN ('.$str.') ORDER BY tpl_id';
            $alleditors = $db->GetArray($sql);
            // table aka CmsLayoutCollection::TPLTABLE
/*          $sql = 'SELECT * FROM '.CMS_DB_PREFIX.'layout_design_tplassoc WHERE tpl_id IN ('.$str.') ORDER BY tpl_id';
            $alldesigns = $db->GetArray($sql);
*/
            // table aka CmsLayoutTemplateCategory::MEMBERSTABLE
            $sql = 'SELECT * FROM '.CMS_DB_PREFIX.CmsLayoutTemplateCategory::MEMBERSTABLE.' WHERE tpl_id IN ('.$str.') ORDER BY tpl_id';
            $allgroups = $db->GetArray($sql);

            // put it all together, into object(s)
            foreach( $rows as $row ) {
                $id = $row['id'];
                $editors = self::filter_editors($id,$alleditors);
                $groups = self::filter_groups($id,$allgroups);
                $out[] = self::create_template($row,$editors,$groups);
            }
        }
        return $out;
    }

    /**
     * Get all templates owned by the specified user
     *
     * @param mixed $a user id (int) or user name (string)
     * @return array CmsLayoutTemplate object(s) or empty
     * @throws CmsInvalidDataException
     */
    public static function get_owned_templates($a) : array
    {
        $id = self::resolve_user($a);
        if( $id <= 0 ) throw new CmsInvalidDataException('Invalid user specified to '.__METHOD__);

        $ob = new CmsLayoutTemplateQuery([ 'u'=>$id ]);
        $list = $ob->GetMatchedTemplateIds();
        if( $list ) {
            return self::get_bulk_templates($list);
        }
        return [];
    }

    /**
     * Get all templates whose originator is the one specified
     * @since 2.3
     *
     * @param string $orig name of originator - core (CmsLayoutTemplate::CORE or '') or a module name
     * @param bool $by_name Optional flag indicating the output format. Default false.
     * @return mixed If $by_name is true then the output will be an array of rows
     *  each with template id and template name. Otherwise, id and CmsLayoutTemplate object
     * @return array CmsLayoutTemplate object(s) or empty
     * @throws CmsInvalidDataException
     */
    public static function get_originated_templates(string $orig, bool $by_name = false) : array
    {
        if( !$orig ) {
            $orig = CmsLayoutTemplate::CORE;
        }
        $ob = new CmsLayoutTemplateQuery([ 'o'=>$orig ]);
        $list = $ob->GetMatchedTemplateIds();
        if( $list ) {
			if( $by_name ) {
		        $db = CmsApp::get_instance()->GetDb();
				$sql = 'SELECT id,name FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE id IN('.implode(',',$list).') ORDER BY name';
				return $db->GetAssoc($sql);
			}
			else {
	            return self::get_bulk_templates($list);
			}
        }
        return [];
    }

    /**
     * Get all templates that the specified user owns or may otherwise edit
     *
     * @param mixed $a user id (int) or user name (string)
     * @return array CmsLayoutTemplate object(s) or empty
     * @throws CmsInvalidDataException
     */
    public static function get_editable_templates($a) : array
    {
        $id = self::resolve_user($a);
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
        return [];
    }

    /**
     * Get all recorded templates
     * @since 2.3
     *
     * @param bool $by_name Optional flag indicating the output format. Default false.
     * @return mixed If $by_name is true then the output will be an array of rows
     *  each with template id and template name. Otherwise, id and CmsLayoutTemplate object
     */
    public static function get_all_templates(bool $by_name = false) : array
    {
        $db = CmsApp::get_instance()->GetDb();

        if( $by_name ) {
            $sql = 'SELECT id,name FROM '.CMS_DB_PREFIX.self::TABLENAME.' ORDER BY modified_date DESC';
            return $db->GetAssoc($sql);
        }
        else {
            $sql = 'SELECT id FROM '.CMS_DB_PREFIX.self::TABLENAME.' ORDER BY modified_date DESC';
            $ids = $db->GetCol($sql);
            return self::get_bulk_templates($ids,false);
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
     *  a (numeric) template-type id, or a CmsLayoutTemplateType object
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
				$corename = CmsLayoutTemplateType::CORE;
                $sql = 'SELECT id FROM '.CMS_DB_PREFIX."layout_tpl_type WHERE name=? AND (originator='$corename' OR originator='' OR originator IS NULL)";
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

    /**
     * Return a set of groups or group-names
     * @since 2.3
     *
     * @param string $prefix An optional group-name prefix to be matched. Default ''.
     * @param bool   $by_name Whether to return group names. Default false
     * @return assoc. array of CmsLayoutTemplateCategory objects or name strings
     */
    public static function get_bulk_groups($prefix = '', $by_name = false)
    {
        $out = [];
        $db = cmsms()->GetDb();
        if( $prefix ) {
            $sql = 'SELECT id,name FROM '.CMS_DB_PREFIX.CmsLayoutTemplateCategory::TABLENAME.' WHERE name LIKE ? ORDER BY name';
            $res = $db->GetAssoc($sql,[$prefix.'%']);
        }
        else {
            $sql = 'SELECT id,name FROM '.CMS_DB_PREFIX.CmsLayoutTemplateCategory::TABLENAME.' ORDER BY name';
            $res = $db->GetAssoc($sql);
        }
        if( $res ) {
            if( $by_name ) {
                $out = $res;
            }
            else {
                foreach( $res as $id => $name ) {
                    $id = (int)$id;
                    try {
                        $out[$id] = CmsLayoutTemplateCategory::load($id);
                    }
                    catch (Exception $e) {
                        //ignore problem
                    }
                }
            }
        }
        return $out;
    }

    /**
     * Return a list of all template-originators
     * @since 2.3
     * $param bool $friendly Optional flag whether to report in UI-friendly format. Default false.
     * @return array name-strings, maybe empty
     */
    public static function get_all_originators(bool $friendly = false) : array
    {
        $db = CmsApp::get_instance()->GetDb();
        $sql = 'SELECT DISTINCT originator FROM '.CMS_DB_PREFIX.self::TABLENAME;
        if( $friendly ) {
            $sql .= ' ORDER BY originator';
        }
        $list = $db->GetCol($sql);
        if( $list ) {
            if( $friendly ) {
                $p = array_search(CmsLayoutTemplate::CORE,$list);
                if( $p !== FALSE ) {
                    unset($list[$p]);
                    $list = [-1 => 'Core'] + $list;
                    return array_values($list);
                }
            }
            return $list;
        }
        return [];
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
        $ob = new CmsLayoutTemplateQuery($params);
        $out = self::get_bulk_templates($ob->GetMatchedTemplateIds());

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

//============= TEMPLATE-OPERATION BACKENDS ============

	/**
	 * Clone template(s) and/or group(s)
	 * @since 2.3
	 * @param mixed $ids int | int[] template identifier(s), < 0 means a group
	 * @return int No of templates cloned
	 */
    public static function operation_copy($ids) : int
	{
		$n = 0;
		list($tpls,$grps) = self::items_split($ids);
		if ($tpls) {
			$sql = 'SELECT name,content,description,contentfile FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE id IN ('.str_repeat('?,',count($tpls)-1).'?)';
			$from = $db->GetArray($sql, $shts);
			$sql = 'INSERT INTO '.CMS_DB_PREFIX.self::TABLENAME.' (name,content,description,contentfile) VALUES (?,?,?,?)';
			foreach ($from as $row) {
				if ($row['name']) {
					$row['name'] = self::get_unique_name($row['name']);
				} else {
					$row['name'] = null;
				}
				if ($row['contentfile']) {
					//TODO clone file
					$row['content'] = TODOfunc($row['name']);
				}
				$db->Execute($sql, $row);
			}
			$n = count($from);
		}
		if ($grps) {
			$sql = 'SELECT id,name,description FROM '.CMS_DB_PREFIX.CmsLayoutTemplateCategory::TABLENAME.' WHERE id IN ('.str_repeat('?,',count($grps)-1).'?)';
			$from = $db->GetArray($sql, $grps);
			$sql = 'SELECT group_id,tpl_id,item_order FROM '.CMS_DB_PREFIX.CmsLayoutTemplateCategory::MEMBERSTABLE.' WHERE group_id IN ('.str_repeat('?,',count($grps)-1).'?)';
			$members = $db->Execute($sql, $grps);
			$sql = 'INSERT INTO '.CMS_DB_PREFIX.CmsLayoutTemplateCategory::TABLENAME.' (name,description) VALUES (?,?)';
			$sql2 = 'INSERT INTO '.CMS_DB_PREFIX.CmsLayoutTemplateCategory::MEMBERSTABLE.' (group_id,tpl_id,item_order) VALUES (?,?,?)';
			foreach ($from as $row) {
				if ($row['name']) {
					$name = self::get_unique_name($row['name']);
				} else {
					$name = null;
				}
				$db->Execute($sql, [$name, $row['description']]);
				$to = $db->Insert_ID();
				$from = $row['id'];
				foreach ($members as $grprow) {
					if ($grprow['group_id'] == $from) {
						$db->Execute($sql2, [$to, $grprow['tpl_id'], $grprow['item_order']]);
					}
				}
			}
			$n += count($from);
		}
		return $n;
	}

	/**
	 * Delete template(s) and/or group(s) but not group members (unless also specified individually)
	 * @since 2.3
	 * @param mixed $ids int | int[] template identifier(s), < 0 means a group
	 * @return int No of pages modified
	 */
	public static function operation_delete($ids) : int
	{
		$db = CmsApp::get_instance()->GetDb();
		list($tpls,$grps) = self::items_split($ids);
		if ($grps) {
			$sql = 'DELETE FROM '.CMS_DB_PREFIX.CmsLayoutTemplateCategory::MEMBERSTABLE.' WHERE group_id IN ('.str_repeat('?,',count($grps)-1).'?)';
			$db->Execute($sql, $grps);
			$sql = 'DELETE FROM '.CMS_DB_PREFIX.CmsLayoutTemplateCategory::TABLENAME.' WHERE id IN ('.str_repeat('?,',count($grps)-1).'?)';
			$db->Execute($sql, $grps);
		}
		if ($tpls) {
			list($pages, $skips) = self::affected_pages($tpls);
			$sql = 'UPDATE '.CMS_DB_PREFIX.'content SET template_id=0 WHERE template_id IN ('.str_repeat('?,',count($pages)-1).'?)';
			$db->Execute($sql, $pages);
			$n = $db->affected_rows();
/* N/A in some db versions
			$sql = 'DELETE FROM '.CMS_DB_PREFIX.self::TABLENAME.' T WHERE T.id IN ('.str_repeat('?,',count($tpls)-1).'?)';
			$sql .= ' AND T.id NOT IN (SELECT template_id FROM '.CMS_DB_PREFIX.'content WHERE template_id=T.id LIMIT 1)';
*/
/* MAYBE AVAILABLE, SLOWER
			$sql = 'DELETE FROM '.CMS_DB_PREFIX.self::TABLENAME.' T WHERE T.id IN ('.str_repeat('?,',count($tpls)-1).'?)';
			$sql .= ' AND T.id NOT IN (SELECT DISTINCT template_id FROM '.CMS_DB_PREFIX.'content WHERE template_id=T.id)';
*/
//* ULTIMATE FALLBACK
			$sql = 'SELECT DISTINCT template_id FROM '.CMS_DB_PREFIX.'content WHERE template_id IN ('.str_repeat('?,',count($tpls)-1).'?)';
			$keeps = $db->GetCol($sql, $tpls);
			$sql = 'DELETE FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE id IN ('.str_repeat('?,',count($tpls)-1).'?)';
			if ($keeps) {
				$sql .= ' AND id NOT IN ('.implode(',',$keeps).')';
			}
//*/
			$db->Execute($sql, $tpls);

			return $n;
		}
		return 0;
	}

	/**
	 * Delete template(s) and/or group(s) and group-member(s)
	 * @since 2.3
	 * @param mixed $ids int | int[] template identifier(s), < 0 means a group
	 * @return int No of pages modified
	 */
	public static function operation_deleteall($ids) : int
	{
		$db = CmsApp::get_instance()->GetDb();
		list($tpls,$grps) = self::items_split($ids);
		if ($grps) {
			$sql = 'SELECT DISTINCT tpl_id FROM '.CMS_DB_PREFIX.CmsLayoutTemplateCategory::MEMBERSTABLE.' WHERE group_id IN ('.str_repeat('?,',count($grps)-1).'?)';
			$members = $db->GetCol($sql, $grps);
			$tpls = array_unique(array_merge($tpls, $members));
			$sql = 'DELETE FROM '.CMS_DB_PREFIX.CmsLayoutTemplateCategory::MEMBERSTABLE.' WHERE group_id IN ('.str_repeat('?,',count($grps)-1).'?)';
			$db->Execute($sql, $grps);
			$sql = 'DELETE FROM '.CMS_DB_PREFIX.CmsLayoutTemplateCategory::TABLENAME.' WHERE id IN ('.str_repeat('?,',count($grps)-1).'?)';
			$db->Execute($sql, $grps);
		}
		if ($tpls) {
			list($pages, $skips) = self::affected_pages($tpls);
			$sql = 'UPDATE '.CMS_DB_PREFIX.'content SET template_id=NULL WHERE template_id IN ('.str_repeat('?,',count($pages)-1).'?)';
			$db->Execute($sql, $pages);
			$n = $db->affected_rows();
			$sql = 'SELECT DISTINCT template_id FROM '.CMS_DB_PREFIX.'content WHERE template_id IN ('.str_repeat('?,',count($tpls)-1).'?)';
			$keeps = $db->GetCol($sql, $tpls);
			$sql = 'DELETE FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE id IN ('.str_repeat('?,',count($tpls)-1).'?)';
			if ($keeps) {
				$sql .= ' AND id NOT IN ('.implode(',',$keeps).')';
			}
			$db->Execute($sql, $tpls);

			return $n;
		}
		return 0;
	}

	/**
	 * Replace the template wherever used and the user is authorized
	 * @since 2.3
	 * @param int $from template identifier
	 * @param int $to template identifier
	 * @return int No of pages modified
	 */
	public static function operation_replace(int $from, int $to) : int
	{
		if ($from < 1 || $to < 1) return 0;
		$uid = get_userid();
		$modify_all = check_permission($uid,'Manage All Content') || check_permission($uid,'Modify Any Page');
		$sql = 'UPDATE '.CMS_DB_PREFIX.'content SET template_id=? WHERE template_id=?';
		if ($modify_all) {
			$args = [$to, $from];
		} else {
			$sql .= ' AND owner_id=?';
			$args = [$to, $from, $uid];
		}
		$db = CmsApp::get_instance()->GetDb();
		$db->Execute($sql, $args);
		return $db->affected_rows();
	}

	/**
	 * Set the template for all pages where the user is authorized
	 * @since 2.3
	 * @param int $to template identifier
	 * @return int No of pages modified
	 */
	public static function operation_applyall(int $to) : int
	{
		if ($to < 1) return 0;
		$uid = get_userid();
		$modify_all = check_permission($uid,'Manage All Content') || check_permission($uid,'Modify Any Page');
		$sql = 'UPDATE '.CMS_DB_PREFIX.'content SET template_id=?';
		if ($modify_all) {
			$args = [$to];
		} else {
			$sql .= ' WHERE owner_id=?';
			$args = [$to, $uid];
		}
		$db = CmsApp::get_instance()->GetDb();
		$db->Execute($sql, $args);
		return $db->affected_rows();
	}

	/**
	 * Migrate template(s) from database storage to file
	 * @since 2.3
	 * @param mixed $ids int | int[] template identifier(s)
	 * @return int No. of files processed
	 */
	public static function operation_export($ids) : int
	{
		$n = 0;
		list($tpls,$grps) = self::items_split($ids);
		if ($tpls) {
			$db = CmsApp::get_instance()->GetDb();
			$sql = 'SELECT id,name,content FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE contentfile=0 AND id IN ('.str_repeat('?,',count($tpls)-1).'?)';
			$from = $db->GetArray($sql, $tpls);
			$sql = 'UPDATE '.CMS_DB_PREFIX.self::TABLENAME.' SET content=?,contentfile=1 WHERE id=?';
			$config = cms_config::get_instance();
			foreach ($from as $row) {
				if ($row['name']) {
					//replicate object::set_content_file()
					$fn = munge_string_to_url($row['name']).'.'.$row['id'].'.tpl';
					//replicate object::get_content_filename()
					$outfile = cms_join_path($config['assets_path'],'templates',$fn);
					$res = file_put_contents($outfile,$row['content'],LOCK_EX);
					if ($res !== false) {
						$db->Execute($sql, [$fn,$row['id']]);
						++$n;
					} else {
						//some signal needed
					}
				}
			}
		}
		return $n;
	}

	/**
	 * Migrate template(s) from file storage to database
	 * @since 2.3
	 * @param mixed $ids int | int[] template identifier(s)
	 * @return int No. of files processed
	 */
	public static function operation_import($ids) : int
	{
		$n = 0;
		list($tpls,$grps) = self::items_split($ids);
		if ($tpls) {
			$sql = 'SELECT id,name,content FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE contentfile=1 AND id IN ('.str_repeat('?,',count($tpls)-1).'?)';
			$from = $db->GetArray($sql, $shts);
			$sql = 'UPDATE '.CMS_DB_PREFIX.self::TABLENAME.' SET content=?,contentfile=0 WHERE id=?';
			$config = cms_config::get_instance();
			foreach ($from as $row) {
				if ($row['name']) {
					//replicate object::set_content_file()
					$fn = munge_string_to_url($row['name']).'.'.$row['id'].'.tpl';
					//replicate object::get_content_filename()
					$outfile = cms_join_path($config['assets_path'],'templates',$fn);
					$content = file_get_contents($outfile);
					if ($content !== false) {
						$db->Execute($sql, [$content,$row['id']]);
						++$n;
					} else {
						//some signal needed
					}
	            }
	        }
		}
		return $n;
	}

	/**
	 * @ignore
	 */
	protected static function affected_pages($ids)
	{
		$uid = get_userid();
		$modify_all = check_permission($uid,'Manage All Content') || check_permission($uid,'Modify Any Page');
		$sql = 'SELECT content_id,template_id FROM '.CMS_DB_PREFIX.'content WHERE template_id';
		$fillers = (is_array($ids)) ? ' IN ('.str_repeat('?,',count($ids)-1).'?)' :  '=?';
		$sql .= $fillers;
		$args = (is_array($ids)) ? $ids : [$ids];
		if (!$modify_all) {
			$sql .= ' AND owner_id=?';
			$args[] = $uid;
		}
        $db = CmsApp::get_instance()->GetDb();
		$valid = $db->getArray($sql, $args);

		if (!$modify_all) {
			$sql = 'SELECT COUNT(1) AS num FROM '.CMS_DB_PREFIX.'content WHERE template_id'.$fillers;
			$args = (is_array($ids)) ? $ids : [$ids];
			$all = $db->getOne($sql, $args);
			$other = $all - count($valid);
		} else {
			$other = 0;
		}
		return [$valid, $other];
	}

	/**
	 * @ignore
	 */
	protected static function items_split($ids)
	{
		$sngl = [];
		$grp = [];
		if(is_array($ids)) {
			foreach ($ids as $id) {
				if ($id > 0) {
					$sngl[] = $id;
				} else {
					$grp[] = -$id;
				}
			}
		} elseif ($ids > 0) {
			$sngl[] = $ids;
		} else {
			$grp[] = -$ids;
		}
		return [$sngl, $grp];
	}
} // class
