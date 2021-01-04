<?php
/*
News module class: Article
Copyright (C) 2016-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.

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
namespace News;

use cms_config;
use cms_utils;
use CMSMS\ContentOperations;
use CMSMS\UserOperations;
use News\Utils;
use function munge_string_to_url;

class Article
{
/*
    const KEYS = [
	'author',
	'author_id',
	'authorname',
	'canonical',
	'category',
	'category_id',
	'content',
	'enddate',
	'extra',
	'id',
	'news_url',
	'params',
	'returnid',
	'startdate',
	'status',
	'summary',
	'title',
	'useexp',
    ];
*/
    private $_rawdata = [];
    private $_meta = [];
    private $_inparams = [];
    private $_inid = 'm1_';

    private function _getdata(string $key)
    {
        $res = null;
        if( isset($this->_rawdata[$key]) ) $res = $this->_rawdata[$key];
        return $res;
    }


    private function _getauthorinfo(int $author_id, bool $authorname = FALSE)
    {
        if( !isset($this->_meta['author']) ) {
            $mod = cms_utils::get_module('News');
            $this->_meta['author'] = $mod->Lang('anonymous');
            $this->_meta['authorname'] = $mod->Lang('unknown');
            if( $author_id > 0 ) {
                $userops = UserOperations::get_instance();
                $theuser = $userops->LoadUserById($author_id);
                if( is_object($theuser) ) {
                    $this->_meta['author'] = $theuser->username;
                    $this->_meta['authorname'] = $theuser->firstname.' '.$theuser->lastname; // is there some locale way we can do this?
                }
            }
            elseif( $author_id < 0 ) {
                $author_id = 0;
            }
        }
        if( $authorname ) return $this->_meta['authorname'];
        return $this->_meta['author'];
    }


    private function _get_returnid()
    {
        if( !isset($this->_meta['returnid']) ) {
            $mod = cms_utils::get_module('News');
            $tmp = $mod->GetPreference('detail_returnid',-1);
            if( $tmp <= 0 ) $tmp = ContentOperations::get_instance()->GetDefaultContent();
            $this->_meta['returnid'] = $tmp;
        }
        return $this->_meta['returnid'];
    }


    private function _get_canonical()
    {
        if( !isset($this->_meta['canonical']) ) {
            $tmp = $this->news_url;
            if( $tmp == '' ) {
                $aliased_title = munge_string_to_url($this->title);
                $tmp = 'news/'.$this->id.'/'.$this->returnid."/{$aliased_title}";
            }
            $mod = cms_utils::get_module('News');
            $canonical = $mod->create_url($this->_inid,'detail',$this->returnid,$this->params,false,false,$tmp);
            $this->_meta['canonical'] = $canonical;
        }
        return $this->_meta['canonical'];
    }


    private function _get_params()
    {
        $params = $this->_inparams;
        $params['articleid'] = $this->id;
        return $params;
    }


    public function set_linkdata($id,$params,$returnid = '')
    {
        if( $id ) $this->_inid = $id;
        if( is_array($params) ) $this->_inparams = $params;
        if( $returnid != '' ) $this->_meta['returnid'] = $returnid;
    }

/*
    public function set_field(Field $field)
    {
        if( !isset($this->_rawdata['fieldsbyname']) ) $this->_rawdata['fieldsbyname'] = [];
        $name = $field->name;
        $this->_rawdata['fieldsbyname'][$name] = $field;
    }


    public function unset_field($name)
    {
        if( isset($this->_rawdata['fieldsbyname']) ) {
            if( isset($this->_rawdata['fieldsbyname'][$name]) ) unset($this->_rawdata['fieldsbyname'][$name]);
            if( count($this->_rawdata['fieldsbyname']) == 0 ) unset($this->_rawdata['fieldsbyname']);
        }
    }
*/

    public function __get($key)
    {
        switch( $key ) {
        case 'id':
        case 'author_id':
        case 'title':
        case 'content':
        case 'summary':
        case 'extra':
        case 'news_url':
        case 'category_id':
        case 'status':
            return $this->_getdata($key);

        case 'startdate':
        case 'enddate':
        case 'create_date':
        case 'modified_date':
			// timestamp.
            return date('Y-m-d H:i',$this->_getdata($key));

        case 'file_location':
            $config = cms_config::get_instance();
            $url = $config['uploads_url'].'/news/id'.$this->id;
            return $url;

        case 'author':
            // metadata.
            return $this->_getauthorinfo($this->author_id);

        case 'authorname':
            // metadata.
            return $this->_getauthorinfo($this->author_id,TRUE);

        case 'category':
            // metadata.
            return Utils::get_category_name_from_id($this->category_id);

        case 'useexp':
            if( isset($this->_meta['useexp']) ) return $this->_meta['useexp'];
            return 0;

        case 'canonical':
            // metadata
            return $this->_get_canonical();

        case 'returnid':
            // metadata
            return $this->_get_returnid();

        case 'params':
            // metadata
            return $this->_get_params();

        case 'start':
        case 'stop':
        case 'created':
        case 'modified':
            //TODO
            break;

        default:
/*          // check if there is a field with this alias
            if( isset($this->_rawdata['fieldsbyname']) && is_array($this->_rawdata['fieldsbyname']) ) {
                foreach( $this->_rawdata['fieldsbyname'] as $fname => &$obj ) {
                    if( !is_object($obj) ) continue;
                    if( $key == $obj->alias ) return $obj->value;
                }
                unset($obj);
            }
*/
// assert IF DEBUGGING
//          throw new Exception('Requesting invalid data from News article object '.$key);
        }
    }


    public function __isset($key)
    {
        switch( $key )
        {
        case 'id':
        case 'author_id':
        case 'title':
        case 'content':
        case 'summary':
        case 'extra':
        case 'news_url':
        case 'category_id':
        case 'status':
            return isset($this->_rawdata[$key]);

        case 'author':
        case 'authorname':
        case 'category':
        case 'canonical':
        case 'returnid':
        case 'params':
        case 'useexp':
            return TRUE;

        case 'startdate':
        case 'enddate':
        case 'modified_date':
            return !empty($this->_rawdata[$key]);

        case 'create_date':
            if( $this->id != '' ) return TRUE;
            break;

        case 'start':
        case 'stop':
        case 'created':
        case 'modified':
            //TODO
            break;

//        default: assert IF DEBUGGING
//            throw new Exception('Requesting invalid data from News article object '.$key);
        }

        return FALSE;
    }


    public function __set($key,$value)
    {
        switch( $key ) {
        case 'id':
        case 'author_id':
        case 'title':
        case 'content':
        case 'summary':
        case 'extra':
        case 'news_url':
        case 'category_id':
            $this->_rawdata[$key] = $value;
            break;

        case 'status':
            $value = strtolower($value);
            if( $value != 'published' &&  $value != 'final' ) $value = 'draft';
            $this->_rawdata[$key] = $value;
            break;

        case 'useexp':
            // this is a different case as this doesn't get stored in the database
            $this->_meta['useexp'] = $value;
            break;

        case 'create_date':
        case 'modified_date':
        case 'startdate':
        case 'enddate':
			// timestamp
            if( is_int($value) ) {
	            $this->_rawdata[$key] = $value;
			}
			else {
	            $this->_rawdata[$key] = strtotime($value);
            }
            break;

//        default: assert IF DEBUGGING
//            throw new Exception('Modifying invalid data in News article object '.$key);
        }
    }
}
