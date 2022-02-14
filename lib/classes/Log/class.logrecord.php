<?php
/*
Class to populate and validate the constents of a record to be added to the admin log.
Copyright (C) 2022-2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

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

use InvalidArgumentException;
use LogicException;

class logrecord
{
    const TYPE_MSG = 0;
    const TYPE_NOTICE = 1;
    const TYPE_WARNING = 2;
    const TYPE_ERROR = 3;

    private $_data = [
       'ip_addr' => null,
       'item_id' => null,
       'message' => null,
       'severity' => null,
       'subject' => null,
       'timestamp' => null,
       'user_id' => null,
       'username' => null,
    ];

    public function __construct(array $params)
    {
        $this->_data['timestamp'] = time();

        foreach ($params as $key => $val) {
            switch( $key) {
            case 'timestamp':
            case 'severity':
                $this->_data[$key] = (int)$val;
                break;

            case 'user_id':
            case 'item_id':
                if (is_int($val) && $val > 0) $this->_data[$key] = $val;
                break;

            case 'ip_addr':
            case 'username':
                if (is_string($val)) $this->_data[$key] = trim($val);
                break;

            case 'subject':
            case 'message':
                $this->_data[$key] = trim($val);
                break;
            }
        }

        // check for valid timestamp and severity, and a message.
        if ($this->timestamp < 1) throw new InvalidArgumentException('Invalid timestamp in '.__CLASS__);
        if ($this->severity < self::TYPE_MSG || $this->severity > self::TYPE_ERROR) throw new InvalidArgumentException('Invalid severity value in '.__CLASS__);
        if (!($this->message || $this->subject)) throw new InvalidArgumentException('Message and/or subject is required in '.__CLASS__);
    }

    public function __get( $key)
    {
        switch( $key) {
        case 'timestamp':
        case 'severity':
            return (int)$this->_data[$key];

        case 'user_id':
        case 'item_id':
            return (string)$this->_data[$key];

        case 'ip_addr':
        case 'username':
        case 'subject':
        case 'message':
            return trim($this->_data[$key]);

        default:
            throw new LogicException("$key is not a gettable member of ".__CLASS__);
        }
    }
}
