<?php
# Definitions of various CMSMS-specific exception classes.
# Copyright (C) 2012-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
# Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.
# This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# You should have received a copy of the GNU General Public License
# along with this program. If not, see <https://www.gnu.org/licenses/>.

//namespace CMSMS;

use CMSMS\LangOperations;

/**
 * The base CMSMS exception class. It preserves extended information, and
 * interprets error-messages which are an integer 'code' or a lang-key.
 * A code is assumed to be a reference to a lang-key like 'CMSEX_thatcode'.
 * If the provided message-string contains space(s) it is not translated.
 *
 * @package CMS
 * @since 1.10
 */
class CmsException extends Exception
{
    /**
    * @ignore
    */
    private $_extra;

    /**
    * Constructor
    * This method accepts variable arguments
    *
    * e.g.throw new CmsException($msg_str,$msg_code,$prev)
    * e.g.throw new CmsException($msg_str,$msg_code,$extra,$prev)
    *
    * @see Exception
    */
    public function __construct(...$args)
    {
        $msg = $args[0] ?? '';
        $code = $args[1] ?? 0;
        $prev = $args[2] ?? null; //Throwable | null, or something else for $this->_extra
        $tmp = $args[3] ?? null;  //ditto, if present
        if( $prev instanceof Throwable ) {
            $this->_extra = $tmp;
        }
        else {
            $this->_extra = $prev;
            if( $tmp instanceof Throwable ) {
                $prev = $tmp;
            }
            else {
                $prev = null;
            }
        }

        parent::__construct($msg,(int)$code,$prev);

        if( is_int($this->message) ) {
            $this->messsage = 'CMSEX_'.$msg;
            if( !LangOperations::key_exists($this->message) ) {
                $this->message = 'MISSING TRANSLATION FOR '.$this->message;
            }
        }
        if( strpos($this->message,' ') === FALSE && LangOperations::key_exists($this->message) ) {
            $this->message = LangOperations::lang($this->message);
        }
    }

    /**
     * Return extra data associated with the exception
     * @return mixed
     */
    public function GetExtraData()
    {
        return $this->_extra;
    }
}

/**
 * Backward compatibility, unlikely to be used
 */
class_alias('CmsException','CmsExtraDataException', false);

/**
 * A CMSMS Logic Exception
 *
 * @package CMS
 * @since 1.10
 */
class CmsLogicException extends CmsException {}

/**
 * A CMSMS Communications Exception
 *
 * @package CMS
 * @since 1.10
 */
class CmsCommunicationException extends CmsException {}

/**
 * A CMSMS Privacy Exception
 *
 * @package CMS
 * @since 1.10
 */
class CmsPrivacyException extends CmsException {}

/**
 * A CMSMS Singleton Exception
 *
 * @package CMS
 * @since 1.10
 */
class CmsSingletonException extends CmsException {}

/**
 * An exception indicating invalid data was supplied to a function or class.
 *
 * @package CMS
 * @since 1.10
 */
class CmsInvalidDataException extends CmsLogicException {}

/**
 * An exception indicating that the requested data could not be found.
 *
 * @package CMS
 * @since 1.10
 */
class CmsDataNotFoundException extends CmsException {}

/**
 * An exception indicating that a 404 error should be supplied.
 *
 * @package CMS
 * @since 2.3
 */
class CmsError400Exception extends CmsException {}

/**
 * An exception indicating that a 404 error should be supplied.
 *
 * @package CMS
 * @since 1.11
 */
class CmsError404Exception extends CmsException {}

/**
 * An exception indicating that a 403 error should be supplied.
 *
 * @package CMS
 * @since 1.12
 */
class CmsError403Exception extends CmsException {}

/**
 * An exception indicating that the install is temporarily unavailable
 * (down for maintenance)
 *
 * @package CMS
 * @since 1.12
 */
class CmsError503Exception extends CmsException {}

/**
 * An exception indicating that content processing should stop, but
 * there is no error to display.
 *
 * @package CMS
 * @since 2.3
 */
class CmsStopProcessingContentException extends CmsException {}

/**
 * An exception indicating an error with a content object
 *
 * @package CMS
 * @since 2.0
 */
class CmsContentException extends CmsException {}

/**
 * An exception indicating an error when editing content.
 *
 * @package CMS
 * @since 1.11
 */
class CmsEditContentException extends CmsContentException {}

/**
 * An exception indicating an SQL Error.
 *
 * @package CMS
 * @since 2.0
 */
class CmsSQLErrorException extends CmsException {}


/**
 * An exception indicating an XML Error.
 *
 * @package CMS
 * @since 2.0
 */
class CmsXMLErrorException extends CmsException {}

/**
 * An exception indicating a problem with a file, directory, or filesystem.
 *
 * @package CMS
 * @since 2.0
 */
class CmsFileSystemException extends CmsException {}

/**
 * A throwable indicating a need to replace something deprecated.
 *
 * @package CMS
 * @since 2.9
 */
class DeprecationNotice extends AssertionError
{
    public function __construct(...$args)
    {
        $type = $args[0] ?? '';
        $replace = $args[1] ?? $args[0];
        if ($replace && !$type) $type = 'function';
        switch ($type) {
            case 'function':
            case 'method':
            $msg = 'Instead call '.$type.' '.$replace.'()';
            break;
            case 'class':
            case 'parameter':
            case 'property':
            $msg = 'Instead use '.$type.' '.$replace;
            default:
            $msg = $type;
        }
        parent::__construct($msg);
    }
}
