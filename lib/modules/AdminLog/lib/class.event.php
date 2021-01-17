<?php

namespace AdminLog;

use InvalidArgumentException;
use LogicException;

class event
{
    const TYPE_MSG = 0;
    const TYPE_NOTICE = 1;
    const TYPE_WARNING = 2;
    const TYPE_ERROR = 3;

    private $_data = [
     'ip_addr' => null,
     'item_id' => null,
     'msg' => null,
     'severity' => null,
     'subject' => null,
     'timestamp' => null,
     'uid' => null,
     'username' => null
    ];

    public function __construct( array $params )
    {
        $this->_data['timestamp'] = time();

        foreach( $params as $key => $val ) {
            switch( $key ) {
            case 'timestamp':
            case 'severity':
                $this->_data[$key] = (int) $val;
                break;

            case 'uid':
            case 'item_id':
                if( is_int($val) && $val > 0 ) $this->_data[$key] = $val;
                break;

            case 'ip_addr':
            case 'username':
                if( is_string($val) ) $this->_data[$key] = $val;
                break;

            case 'subject':
            case 'msg':
                $this->_data[$key] = trim($val);
                break;
            }
        }

        // validate this thing.  we need a timestamp, a severity, and a message.
        if( $this->timestamp < 1 ) throw new InvalidArgumentException('value for timestamp in an '.self::class.' is invalid');
        if( $this->severity < 0 || $this->severity > 3 ) throw new InvalidArgumentException('value for severity in an '.self::class.' is invalid');
        if( !$this->msg )  throw new InvalidArgumentException('value for msg in an '.self::class.' cannot be empty');
    }

    public function __get( $key )
    {
        switch( $key ) {
        case 'timestamp':
        case 'severity':
        case 'uid':
            return (int) $this->_data[$key];

        case 'item_id':
            if( !is_null($this->_data[$key]) ) return (int) $this->_data[$key];
            return;

        case 'ip_addr':
        case 'username':
        case 'subject':
        case 'msg':
            return trim($this->_data[$key]);

        default:
            throw new LogicException("$key is not a gettable member of ".self::class);
        }
    }
} // class
