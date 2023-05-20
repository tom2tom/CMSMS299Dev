<?php
/*
News module class: Article
Copyright (C) 2016-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/
namespace News;

use CMSMS\Lone;
use CMSMS\Utils as AppUtils;
use News\Utils;
use function munge_string_to_url;

class Article
{
/*  const KEYS = [
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
    'image_url',
    'news_url',
    'params',
    'returnid',
    'startdate',
    'status',
    'summary',
    'title',
    ];
*/
    private $_rawdata = [];
    private $_meta = [];
    private $_inparams = [];
    private $_inid = 'm1_';

    private function _getdata(string $key)// : mixed
    {
        return $this->_rawdata[$key] ?? null;
    }

    private function _getauthorinfo(int $author_id, bool $authorname = FALSE)
    {
        if( !isset($this->_meta['author']) ) {
            $mod = AppUtils::get_module('News');
            $this->_meta['author'] = $mod->Lang('anonymous');
            $this->_meta['authorname'] = $mod->Lang('unknown');
            if( $author_id > 0 ) {
                $userops = Lone::get('UserOperations');
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
            $mod = AppUtils::get_module('News');
            $tmp = $mod->GetPreference('detail_returnid',-1);
            if( $tmp <= 0 ) $tmp = Lone::get('ContentOperations')->GetDefaultContent();
            $this->_meta['returnid'] = $tmp;
        }
        return $this->_meta['returnid'];
    }

    private function _get_canonical()
    {
        if( !isset($this->_meta['canonical']) ) {
            $value = $this->news_url;
            if( !$value ) {
                $value = munge_string_to_url($this->title); // TODO better version
            }
            $purl = 'News/'.$this->id.'/'.$this->returnid.'/'.$value;
            $mod = AppUtils::get_module('News');
            $canonical = $mod->create_url($this->_inid,'detail',$this->returnid,$this->params,false,false,$purl,false,2);
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

    // ensure we get a datetime-field-compatible value
    private function dtform($key, $value)
    {
        if( is_int($value) ) { // timestamp?
            $this->_rawdata[$key] = date('Y-m-d H:i:s', $value); // c.f. $db->DbTimeStamp($value,false) which also escapes content
        }
        else {
            $this->_rawdata[$key] = $value; // just assume it's ok .... BAH!
        }
    }

    public function set_linkdata($id,$params,$returnid = '')
    {
        if( $id ) $this->_inid = $id;
        if( is_array($params) ) $this->_inparams = $params;
        if( $returnid != '' ) $this->_meta['returnid'] = $returnid;
    }

/*  public function set_field(Field $field)
    {
        if( !isset($this->_rawdata['fieldsbyname']) ) $this->_rawdata['fieldsbyname'] = [];
        $name = $field->name;
        $this->_rawdata['fieldsbyname'][$name] = $field;
    }


    public function unset_field($name)
    {
        if( isset($this->_rawdata['fieldsbyname']) ) {
            if( isset($this->_rawdata['fieldsbyname'][$name]) ) unset($this->_rawdata['fieldsbyname'][$name]);
            if( !$this->_rawdata['fieldsbyname'] ) unset($this->_rawdata['fieldsbyname']);
        }
    }
*/
    #[\ReturnTypeWillChange]
    public function __get(string $key)// : mixed
    {
        switch( $key ) {
        case 'id':
        case 'author_id':
        case 'title':
        case 'content':
        case 'summary':
        case 'news_url':
        case 'image_url':
        case 'extra':
        case 'category_id':
        case 'status':
        case 'start_time':
        case 'end_time':
        case 'create_date':
        case 'modified_date':
            return $this->_getdata($key);
            // aliases
        case 'start':
            return $this->_getdata('start_time');
        case 'stop':
            return $this->_getdata('end_time');
            // timestamps
        case 'startdate':
            return strtotime($this->_getdata('start_time')); // TODO if NULL
        case 'enddate':
            return strtotime($this->_getdata('end_time')); // TODO if NULL
        case 'created':
            return strtotime($this->_getdata('create_date'));
        case 'modified':
            return strtotime($this->_getdata('modified_date')); // TODO if NULL

        case 'file_location':
            $config = Lone::get('Config');
            $url = $config['uploads_url'].'/news/id'.$this->id;
            return $url;

        case 'author':
            // metadata
            return $this->_getauthorinfo($this->author_id);

        case 'authorname':
            // metadata
            return $this->_getauthorinfo($this->author_id,TRUE);

        case 'category':
            // metadata
            return Utils::get_category_name_from_id($this->category_id);
/*
        case 'useexp':
            if( isset($this->_meta['useexp']) ) return $this->_meta['useexp'];
            return 0;
*/
        case 'canonical':
            // metadata
            return $this->_get_canonical();

        case 'returnid':
            // metadata
            return $this->_get_returnid();

        case 'params':
            // metadata
            return $this->_get_params();

        default:
/*          // check if there is a field with this news_url
            if( isset($this->_rawdata['fieldsbyname']) && is_array($this->_rawdata['fieldsbyname']) ) {
                foreach( $this->_rawdata['fieldsbyname'] as $fname => &$obj ) {
                    if( !is_object($obj) ) continue;
                    if( $key == $obj->news_url) return $obj->value;
                }
                unset($obj);
            }
*/
// assert IF DEBUGGING
//          throw new Exception('Requesting invalid data from News article object '.$key);
        }
        return null;
    }

    public function __isset(string $key) : bool
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
        case 'image_url':
        case 'category_id':
        case 'status':
        case 'start_time':
        case 'end_time':
        case 'create_date':
        case 'modified_date':
            return !empty($this->_rawdata[$key]);

        case 'author':
        case 'authorname':
        case 'category':
        case 'canonical':
        case 'returnid':
        case 'params':
        case 'useexp':
            return TRUE;
        // aliases & equivalent stamps
        case 'start':
        case 'startdate':
            return !empty($this->_rawdata['start_time']);
        case 'stop':
        case 'enddate':
            return !empty($this->_rawdata['end_time']);
        case 'created':
            return !empty($this->_rawdata['create_date']);
        case 'modified':
            return !empty($this->_rawdata['modified_date']);

//        default: assert IF DEBUGGING
//            throw new Exception('Requesting invalid data from News article object '.$key);
        }

        return FALSE;
    }

    public function __set(string $key,$value) : void
    {
        switch( $key ) {
        case 'id':
        case 'author_id':
        case 'title':
        case 'content':
        case 'summary':
        case 'news_url': // TODO validation
        case 'image_url': // TODO validation
        case 'extra':
        case 'category_id':
            $this->_rawdata[$key] = $value;
            break;

        case 'status':
            $value = strtolower($value);
            if( $value != 'published' &&  $value != 'final' ) { $value = 'draft'; }
            $this->_rawdata[$key] = $value;
            break;

        case 'useexp':
            // this is a different case as this doesn't get stored in the database
            $this->_meta['useexp'] = $value;
            break;

        case 'create_date':
        case 'modified_date':
        case 'start_time':
        case 'end_time':
            $this->dtform($key,$value);
            break;
            //aliases
        case 'created':
           $this->dtform('create_date',$value);
            break;
        case 'modified':
           $this->dtform('modified_date',$value);
            break;
        case 'startdate':
           $this->dtform('start_time',$value);
            break;
        case 'enddate':
           $this->dtform('end_time',$value);
            break;

//        default: assert IF DEBUGGING
//            throw new Exception('Modifying invalid data in News article object '.$key);
        }
    }
}
