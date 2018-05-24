<?php
# Class defining folder-specific properties and roles, and/or permitted users/groups
# Copyright (C) 2016-2018 Robert Campbell <calguy1000@cmsmadesimple.org>
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
 * $obj = new \CMSMS\FilePickerProfile( [ 'type'=>FileType::TYPE_IMAGE,
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
 * @property-read int $show_thumbs Whether thumbnail images should be shown in place of normal icons for images.
 * @property-read int $show_hidden Indicates that hidden files should be shown in the filepicker.
 * @property-read mixed $sort Indicates whether and how files should be sorted before listing them in the filepicker.
 *   FilePickerProfile::FLAG_NO, or one of 'name','size','date' & optionally appended ',a[sc]' or ',d[esc]'
 * @property-read array $match_groups Since 2.3 group-id's which are permitted to perform the suitably-flagged operations defined in the profile default ['*']
 * @property-read array $exclude_groups Since 2.3 group-id's which may not perform the suitably-flagged operations defined in the profile default []
 * @property-read array $match_users  Since 2.3 user-id's which are permitted to perform the suitably-flagged operations defined in the profile default ['*']
 * @property-read array $exclude_groups Since 2.3 user-id's which may not perform the suitably-flagged operations defined in the profile default []
 */
class FilePickerProfile
{
    const FLAG_NONE = 0;
    const FLAG_NO = 0;
    const FLAG_YES = 1;
    const FLAG_BYGROUP = 2;
    const FLAG_BYUSER = 3;
    const FLAG_BYGRPANDUSR = 4;

    /**
     * @ignore
     */
    private $_data = [
      'can_delete'=>self::FLAG_YES,
      'can_mkdir'=>self::FLAG_YES,
      'can_upload'=>self::FLAG_YES,
      'exclude_groups'=>[],
      'exclude_users'=>[],
      'exclude_prefix'=>null,
      'match_groups'=>['*'],
      'match_users'=>['*'],
      'match_prefix'=>null,
      'show_hidden'=>self::FLAG_NO,
      'show_thumbs'=>self::FLAG_YES,
      'sort'=>'name',
      'top'=>null,
      'type'=>FileType::TYPE_ANY,
    ];

    /**
     * Constructor
     *
     * @param array $params An associative array of params suitable for the setValue method.
     */
    public function __construct( array $params = [] )
    {
        foreach( $params as $key => $val ) {
            $this->setValue($key,$val);
        }
    }

    /**
     * @ignore
     */
    public function __get( string $key )
    {
        switch( $key ) {
        case 'top':
        case 'type':
        case 'match_prefix':
        case 'exclude_prefix':
            return trim($this->_data[$key]);

        case 'can_mkdir':
        case 'can_upload':
        case 'can_delete':
        case 'show_thumbs':
        case 'show_hidden':
            return (int) $this->_data[$key];

        default:
            return $this->_data[$key] ?? null;
        }
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
            case FileType::TYPE_IMAGE:
            case FileType::TYPE_AUDIO:
            case FileType::TYPE_VIDEO:
            case FileType::TYPE_MEDIA:
            case FileType::TYPE_XML:
            case FileType::TYPE_DOCUMENT:
            case FileType::TYPE_ARCHIVE:
            case FileType::TYPE_ANY:
                $this->_data[$key] = $val;
                break;
            case 'file':
                $this->_data[$key] = FileType::TYPE_ANY;
                break;
            default:
                throw new CmsInvalidDataException("$val is an invalid value for type in ".__CLASS__);
                break;
            }
            break;

        case 'can_mkdir':
        case 'can_delete':
        case 'can_upload':
        case 'show_thumbs':
        case 'show_hidden':
            $val = (int) $val;
            switch( $val ) {
            case self::FLAG_NO:
            case self::FLAG_YES:
            case self::FLAG_BYGROUP:
                $this->_data[$key] = $val;
                break;
            default:
                die('val is '.$val);
                throw new CmsInvalidDataException("$val is an invalid value for $key in ".__CLASS__);
            }
            break;

        case 'sort':
           //TODO relevant checks
            $this->_data[$key] = $val;
            break;

        case 'exclude_groups':
        case 'exclude_users':
        case 'match_groups':
        case 'match_users':
           //TODO relevant checks
            $this->_data[$key] = $val;
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
