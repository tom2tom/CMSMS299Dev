<?php
#Class to interact with a page-content type.
#Copyright (C) 2019-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

namespace CMSMS;

/**
 * Convenience class to hold and interact with page-content type parameters
 * @since 2.3 this replaces the former ContentTypePlaceHolder class
 *
 * @package CMS
 */
class ContentType implements \ArrayAccess
{
	/**
	 * @var string The type name, must be unique in the installation
	 */
	public $type;

	/**
	 * @var string A displayable name for the type
	 */
	public $friendlyname;

	/**
	 * @var string The type's (ContentDisplayer-conformant) displayable (and perhaps also editable) class
	 */
	public $class;

	/**
	 * @var string Path of file containing the type displayable class
	 * Relevant only if $class cannot be autoloaded
	 */
	public $filename;

	/**
	 * @var string The type's (ContentEditor-conformant) editable class (if any)
	 * @since 2.3
	 */
	public $editorclass;

	/**
	 * @var string Path of file containing the type editable class
	 * @since 2.3
	 * Relevant only if $editorclass cannot be autoloaded
	 */
	public $editorfilename;

    /**
     * @param mixed $parms Optional parameters assoc. array | null
     */
    public function __construct($parms = null)
    {
        if ($parms) {
            extract($parms);

            if (!empty($type)) {
                $this->type = strtolower($type);
            } elseif (!empty($name)) {
                $this->type = strtolower($name);
            }

            if (!empty($friendlyname)) {
                $this->friendlyname = $friendlyname;
            }

            if (!empty($locator)) {
                if( is_file($locator) ) {
                    $this->class = substr(basename($locator),6,-4);
                    $this->filename = $locator;
                }
                else {
                    $this->class = $locator;
                    $this->filename = null;
                }
            }

            if (!empty($editorlocator)) {
                if( is_file($editorlocator) ) {
                    $this->editorclass = substr(basename($editorlocator),6,-4);
                    $this->editorfilename = $editorlocator;
                }
                else {
                    $this->editorclass = $editorlocator;
                    $this->editorfilename = null;
                }
            }
        }
    }

	public function __call($method, $args)
	{
		$chk = strtolower($method);
		$pre = substr($chk, 0, 3);
		switch ($pre) {
			case 'set':
				$len = ($chk[4] == '_') ? 4 : 3;
				$key = strtolower(substr($chk, $len));
				$this->$key = $args[0];
				break;
			case 'get':
				$len = ($chk[4] == '_') ? 4 : 3;
				$key = strtolower(substr($chk, $len));
				return $this->$key;
		}
	}

    public function offsetExists($key)
    {
        return !empty($this->$key);
    }

    public function offsetGet($key)
    {
        if( isset($this->$key) ) return $this->$key;
    }

    public function offsetSet($key,$value)
    {
		$this->$key = $value;
    }

    public function offsetUnset($key)
    {
		unset($this->$key);
    }
}

\class_alias(ContentType::class, 'CmsContentTypePlaceHolder', false);
