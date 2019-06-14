<?php
#Class to interact with a mact and related action-parameters
#Copyright (C) 2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
#Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.
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

namespace CMSMS\internal;

use JsonSerializable;
use LogicException;
use function cms_to_bool;

/**
 * Class to record and interact with the properties of a mact and related action-parameters
 * @since 2.3
 */
final class MactInfo implements JsonSerializable
{
    const CNTNT01 = 'cntnt01';

    private $_module;

    private $_action;

    private $_id;

    private $_inline;

    private $_params = [];

    public function __get($key)
    {
        switch( $key ) {
            case 'module':
                return $this->_module ?? null;

            case 'action':
                return $this->_action ?? 'default';

            case 'id':
                return $this->_id ?? self::CNTNT01;

            case 'inline':
                if( (isset($this->_id) && $this->_id == self::CNTNT01) || !isset($this->_inline) || !$this->_inline ) return 0;
                return 1;

            case 'params':
                return $this->_params ?? [];

            default:
                throw new LogicException("$key is not a gettable property of ".__CLASS__);
        }
    }

    public function __set($key, $val)
    {
        switch( $key ) {
            case 'module':
            case 'id':
            case 'action':
            case 'inline':
            case 'params':
                $prop = '_'.$key;
                $this->$prop = $val;
                return;
        }
        throw new LogicException("$key is not a settable property of ".self::class);
    }

    /**
     * Generate a MactInfo object representing values in $in
     * @param array $in
     * @return self
     */
    public static function from_array(array $in) : self
    {
        $obj = new self();
        foreach( $in as $key => $val ) {
            switch( $key ) {
                case 'module':
                    $obj->_module = trim($val);
                    break;
                case 'id':
                    $obj->_id = trim($val);
                    break;
                case 'action':
                    $obj->_action = trim($val);
                    break;
                case 'inline':
                    $obj->_inline = is_null($val) ? false : cms_to_bool($val);
                    break;
                case 'params':
                    if( is_array($val) && !empty($val) ) $obj->_params = $val;
                    break;
            }
        }
        return $obj;
    }

    /**
     * Interface method
     */
    public function jsonSerialize()
    {
        $out = [
        'module' => $this->_module ?? null,
        'action' => $this->_action ?? 'default',
        'id' => $this->_id ?? self::CNTNT01,
        'inline' => $this->_inline ?? 0,
        ];
        if( !empty($this->_params) ) $out['params'] = $this->_params;
        return $out;
    }
} // class
