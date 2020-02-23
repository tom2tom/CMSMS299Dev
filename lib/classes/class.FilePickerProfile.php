<?php
# Class defining folder-specific properties and roles
# Copyright (C) 2016-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

use CmsInvalidDataException;
use CMSMS\FilePickerProfile;
use CMSMS\FileType;
use function cms_to_bool;

/**
 * A simple class that defines a profile of information used by a filepicker to indicate how it should
 * behave and what functionality should be provided.
 *
 * This is an immutable class.
 *
 * The constructor and overrideWith methods of this class accept an associative array of parameters (see the properties below)
 * to allow building or altering a profile object.  Ths is the only time when properties of a profile can be adjusted.
 *
 * ```php
 * $obj = new CMSMS\FilePickerProfile( [ 'type'=>FileType::TYPE_IMAGE,
 *    'exclude_prefix'=>'foo' ] );
 *
 * @package CMS
 * @license GPL
 * @author Robert Campbell <calguy1000@cmsmadesimple.org>
 * @since  2.2
 * @property-read string $top The top directory for the filepicker (relative to the CMSMS uploads directory, or ... TODO)
 * @property-read FileType $type A FileType enumerator representing files which may be used.
 * @property-read string $match_prefix List only files/items that have the specified prefix.
 * @property-read string exclude_prefix  Exclude any files/items that have the specified prefix.
 * @property-read int $can_mkdir  Users of the filepicker can create new directories.
 * @property-read int $can_upload  Users of the filepicker can upload new files (of the specified type)
 * @property-read int $can_delete  Users of the filepicker can remove files.
 * @property-read bool $show_thumbs Whether thumbnail images should be shown in place of normal icons for images.
 * @property-read bool $show_hidden Whether hidden files should be shown in the filepicker.
 * @property-read bool $sort Whether files should be sorted before listing them in the filepicker.
 */
class FilePickerProfile
{
    const FLAG_NONE = 0;
    const FLAG_NO = 0;
    const FLAG_YES = 1;
    const FLAG_BYGROUP = 2;

    /**
     * @ignore
     */
    protected $_data = [
       'top'=>null,
       'type'=>FileType::ANY,
       'can_upload'=>self::FLAG_YES,
       'can_delete'=>self::FLAG_YES,
       'can_mkdir'=>self::FLAG_YES,
       'match_prefix'=>null,
       'exclude_prefix'=>null,
       'show_thumbs'=>true,
       'show_hidden'=>false,
       'sort'=>true
    ];

    /**
     * Constructor
     *
     * @param array $params An associative array of params suitable for the setValue method.
     */
    public function __construct( array $params = null )
    {
        if( !is_array($params) || !count($params) ) return;
        foreach( $params as $key => $val ) {
            $this->setValue($key,$val);
        }
    }

    /**
     * @ignore
     */
    public function __get($key)
    {
        switch( $key ) {
        case 'top':
            return trim($this->_data[$key]);

		case 'type': // FileType enum member
		case 'can_mkdir': // self::FLAG_* value
        case 'can_upload':
        case 'can_delete':
            return (int) $this->_data[$key];

        case 'show_thumbs':
        case 'show_hidden':
        case 'sort':
            return (bool) $this->_data[$key];
        }
    }

    /**
     * @ignore
     */
    public function __set( string $key, $val )
    {
		$this->setValue($key, $val);
	}

    /**
     * Set a value into this profile
     *
     * @param string $key The key to set
     * @param mixed $val The value to set.
     */
    protected function setValue( string $key, $val )
    {
        switch( $key ) {
        case 'top':
           //TODO relevant checks
        case 'match_prefix':
        case 'exclude_prefix':
            $this->_data[$key] = trim($val);
            break;

        case 'type':
            $val = trim($val);
            switch( $val ) {
            case FileType::IMAGE:
            case FileType::AUDIO:
            case FileType::VIDEO:
            case FileType::MEDIA:
            case FileType::XML:
            case FileType::DOCUMENT:
            case FileType::ARCHIVE:
            case FileType::ANY:
                $this->_data[$key] = (int)$val; // kill the trim()-conversion
                break;
			case 'image':
				$this->_data[$key] = FileType::IMAGE; //TODO fix this hack - upstream use of string value
            case 'file':
                $this->_data[$key] = FileType::ANY;
                break;
            default:
                throw new CmsInvalidDataException("$val is not a valid type in ".self::class);
            }
            break;

        case 'can_mkdir':
        case 'can_delete':
        case 'can_upload':
            $val = (int) $val;
            switch( $val ) {
            case self::FLAG_NONE:
            case self::FLAG_YES:
            case self::FLAG_BYGROUP:
                $this->_data[$key] = $val;
                break;
            default:
//                die('val is '.$val);
                throw new CmsInvalidDataException("$val is not a valid value for $key in ".self::class);
            }
            break;

        case 'sort':
           //TODO relevant checks
        case 'show_thumbs':
        case 'show_hidden':
            $this->_data[$key] = cms_to_bool( $val );
            break;
        }
    }

    /**
     * Create a new profile object based on the current one, with
     *  property-adjustments per $params.
     *
     * @param array $params Associative array of parameters for the setValue method.
     * @return FilePickerProfile
     */
    public function overrideWith( array $params )
    {
        $obj = clone $this;
        foreach( $params as $key => $val ) {
            $obj->setValue( $key, $val );
        }
        return $obj;
    }

    /**
     * Get the raw data of the profile.
     *
     * @internal
     * @return array
     */
    public function getRawData()
    {
        return $this->_data;
    }
} // class
