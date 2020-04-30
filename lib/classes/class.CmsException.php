<?php
# Definitions of various CMSMS-specific exception classes.
# Copyright (C) 2012-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

namespace CMSMS;

use AssertionError;
use CMSMS\Exception;
use CMSMS\LangOperations;
use Exception as MainException;

/**
 * The base CMSMS exception class. It preserves extended information, and
 * interprets error-messages which are an integer 'code' or a lang-key.
 * A code is assumed to be a reference to a lang-key like 'CMSEX_thatcode'.
 * If the provided message-string contains space(s) it is not translated.
 *
 * @package CMS
 * @since 1.10
 */
class Exception extends MainException
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
        $msg = $args[0] ?? 'Unknown error'; // $args[0] may be explicitly ''
        if( is_numeric($msg) ) {
            $msg = 'CMSEX_'.trim($msg);
            if( LangOperations::key_exists($msg) ) {
                $msg = LangOperations::lang($msg);
            }
            else {
                $msg = 'MISSING TRANSLATION FOR '.$msg;
            }
        }
        elseif( $msg && strpos($msg,' ') === FALSE && LangOperations::key_exists($msg) ) {
            $msg = LangOperations::lang($msg);
        }
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
\class_alias('CMSMS\Exception', 'CmsException', false);

/**
 * Logic exception
 *
 * @package CMS
 * @since 1.10
 */
class LogicException extends Exception {}
\class_alias('CMSMS\LogicException', 'CmsLogicException', false);

/**
 * Communications exception
 *
 * @package CMS
 * @since 1.10
 */
class CommunicationException extends Exception {}
\class_alias('CMSMS\CommunicationException', 'CmsCommunicationException', false);

/**
 * Privacy exception
 *
 * @package CMS
 * @since 1.10
 */
class PrivacyException extends Exception {}
\class_alias('CMSMS\PrivacyException', 'CmsPrivacyException', false);

/**
 * Singleton exception
 *
 * @package CMS
 * @since 1.10
 */
class SingletonException extends Exception {}
\class_alias('CMSMS\SingletonException', 'CmsSingletonException', false);

/**
 * Invalid data exceptions
 *
 * @package CMS
 * @since 1.10
 */
class DataException extends Exception {}
\class_alias('CMSMS\DataException', 'CmsDataException', false);
\class_alias('CMSMS\DataException', 'CmsExtraDataException', false);
\class_alias('CMSMS\DataException', 'CmsInvalidDataException', false);
\class_alias('CMSMS\DataException', 'CmsDataNotFoundException', false);

/**
 * An exception indicating that a 400 error should be supplied.
 *
 * @package CMS
 * @since 2.3
 */
class Error400Exception extends Exception
{
    public function __construct(...$args)
    {
        $args[0] = 'Bad request';
        parent::__construct(...$args);
    }
}
\class_alias('CMSMS\Error400Exception', 'CmsError400Exception', false);

/**
 * An exception indicating that a 403 error should be supplied.
 *
 * @package CMS
 * @since 1.12
 */
class Error403Exception extends Exception
{
    public function __construct(...$args)
    {
        $args[0] = 'Forbidden';
        parent::__construct(...$args);
    }
}
\class_alias('CMSMS\Error403Exception', 'CmsError403Exception', false);

/**
 * An exception indicating that a 404 error should be supplied.
 *
 * @package CMS
 * @since 1.11
 */
class Error404Exception extends Exception 
{
    public function __construct(...$args)
    {
        $args[0] = 'Not found';
        parent::__construct(...$args);
    }
}
\class_alias('CMSMS\Error404Exception', 'CmsError404Exception', false);

/**
 * An exception indicating that the install is temporarily unavailable
 * (down for maintenance)
 *
 * @package CMS
 * @since 1.12
 */
class Error503Exception extends Exception
{
    public function __construct(...$args)
    {
        $args[0] = 'Service unavailable';
        parent::__construct(...$args);
    }
}
\class_alias('CMSMS\Error503Exception', 'CmsError503Exception', false);

/**
 * An exception indicating that content processing should stop, but
 * there is no error to display.
 *
 * @package CMS
 * @since 2.3
 */
class StopProcessingContentException extends Exception
{
    public function __construct(...$args)
    {
        $args[0] = '';
        parent::__construct(...$args);
    }
}
\class_alias('CMSMS\StopProcessingContentException', 'CmsStopProcessingContentException', false);

/**
 * An exception indicating an error with a content object
 *
 * @package CMS
 * @since 2.0
 */
class ContentException extends Exception {}
\class_alias('CMSMS\ContentException', 'CmsContentException', false);

/**
 * An exception indicating an error when editing content.
 *
 * @package CMS
 * @since 1.11
 */
class EditContentException extends ContentException {}
\class_alias('CMSMS\EditContentException', 'CmsEditContentException', false);

/**
 * An exception indicating an SQL Error.
 *
 * @package CMS
 * @since 2.0
 */
class SQLErrorException extends Exception {}
\class_alias('CMSMS\SQLErrorException', 'CmsSQLErrorException', false);

/**
 * An exception indicating an XML Error.
 *
 * @package CMS
 * @since 2.0
 */
class XMLErrorException extends Exception {}
\class_alias('CMSMS\XMLErrorException', 'CmsXMLErrorException', false);

/**
 * An exception indicating a problem with a file, directory, or filesystem.
 *
 * @package CMS
 * @since 2.0
 */
class FileSystemException extends Exception {}
\class_alias('CMSMS\FileSystemException', 'CmsFileSystemException', false);

/**
 * An error-throwable to signal a need to replace something deprecated.
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
