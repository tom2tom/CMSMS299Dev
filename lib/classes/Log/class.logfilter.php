<?php
/*
Class for ...
Copyright (C) 2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful, but
WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/
namespace CMSMS\Log;

use LogicException;

class logfilter
{
    private $_data = [
        'limit' => 100,
        'message' => null,
        'offset' => 0,
        'severity' => -1,
        'subject' => null,
        'username' => null,
    ];

    #[\ReturnTypeWillChange]
    public function __get( $key)// : mixed
    {
        switch( $key) {
        case 'subject':
        case 'username':
        case 'message':
            return trim($this->_data[$key]);

        case 'severity':
        case 'limit':
        case 'offset':
            return (int) $this->_data[$key];

        default:
            throw new LogicException("$key is not a gettable member of ".__CLASS__);
        }
    }

    #[\ReturnTypeWillChange]
    public function __set( $key, $val)// : void
    {
        switch( $key) {
        case 'subject':
        case 'username':
        case 'message':
            $this->_data[$key] = trim($val);
            break;
        case 'severity':
            // allow null or negative value to indicate any severity
            if (is_null($val) || (int) $val < 0) {
                $this->_data[$key] = null;
            }
            else {
                $this->_data[$key] = max(0,min(3,(int)$val));
            }
            break;
        case 'limit':
            $this->_data[$key] = max(1,(int)$val);
            break;
        case 'offset':
            $this->_data[$key] = max(0,(int)$val);
            break;
        default:
            throw new LogicException("$key is not a settable member of ".__CLASS__);
        }
    }
}
