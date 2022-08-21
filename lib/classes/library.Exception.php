<?php
/*
Definitions of various CMSMS-specific exception classes.
Copyright (C) 2012-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/
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
    * e.g.throw new CMSMS\Exception($msg_str,$msg_code,$prev)
    * e.g.throw new CMSMS\Exception($msg_str,$msg_code,$extra,$prev)
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
        elseif( $msg && strpos($msg,' ') === false && LangOperations::key_exists($msg) ) {
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
 * Prepend a generic 'type' to the error message string
 * @since 3.0
 * @param string $msg
 * @param array $args
 */
function prefix_message($msg, &$args)
{
    if( strpos($msg,' ') === false && LangOperations::key_exists($msg) ) {
        $msg = LangOperations::lang($msg);
    }
    if ($args[0]) {
        $msg2 = $args[0];
        if( is_numeric($msg2) ) {
            $msg2 = 'CMSEX_'.trim($msg2);
            if( LangOperations::key_exists($msg2) ) {
                $msg2 = LangOperations::lang($msg2);
            }
            else {
                $msg2 = 'MISSING TRANSLATION FOR '.$msg2;
            }
        }
        elseif( $msg2 && strpos($msg2,' ') === false && LangOperations::key_exists($msg2) ) {
            $msg2 = LangOperations::lang($msg2);
        }
        $args[0] = $msg.' - '.$msg2;
    } else {
        $args[0] = $msg;
    }
}

/**
 * An exception indicating that a 400 error should be supplied.
 *
 * @package CMS
 * @since 3.0
 */
class Error400Exception extends Exception
{
    public function __construct(...$args)
    {
        prefix_message('Bad request', $args);
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
        prefix_message('Forbidden', $args);
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
        prefix_message('Not found', $args);
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
        prefix_message('Service unavailable', $args);
        parent::__construct(...$args);
    }
}
\class_alias('CMSMS\Error503Exception', 'CmsError503Exception', false);

/**
 * An exception indicating that content processing should stop, but
 * there is no error to display.
 *
 * @package CMS
 * @since 3.0
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
class SQLException extends Exception {}
\class_alias('CMSMS\SQLException', 'CmsSQLException', false);

/**
 * An exception indicating an XML Error.
 *
 * @package CMS
 * @since 2.0
 */
class XMLException extends Exception {}
\class_alias('CMSMS\XMLException', 'CmsXMLErrorException', false);

/**
 * An exception indicating a problem with a file, directory, or filesystem.
 *
 * @package CMS
 * @since 2.0
 */
class FileSystemException extends Exception {}
\class_alias('CMSMS\FileSystemException', 'CmsFileSystemException', false);

/**
 * An exception indicating an error creating a lock
 *
 * @package CMS
 * @since 2.0
 */
class LockException extends Exception {}
\class_alias('CMSMS\LockException', 'CmsLockException', false);

/**
 * An exception indicating an error loading or finding a lock
 *
 * @package CMS
 * @since 2.0
 */
class NoLockException extends LockException {}
\class_alias('CMSMS\NoLockException', 'CmsNoLockException', false);

/**
 * An exception indicating an error removing a lock
 *
 * @package CMS
 * @since 2.0
 */
class UnLockException extends LockException {}
\class_alias('CMSMS\UnLockException', 'CmsUnLockException', false);

/**
 * An exception indicating a user operating on a lock is not its owner
 *
 * @package CMS
 * @since 2.0
 */
class LockOwnerException extends LockException {}
\class_alias('CMSMS\LockOwnerException', 'CmsLockOwnerException', false);

/**
 * An error-throwable to signal a need to replace something deprecated.
 *
 * @package CMS
 * @since 3.0
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
            break;
            case 'php':
            $msg = 'Instead use code '.$replace;
            default:
            $msg = $type;
        }
        parent::__construct($msg);
    }
}
