<?php
# Class defining a content filter
# Copyright (C) 2013-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

namespace CMSContentManager;

use InvalidArgumentException;

/**
 * A simple class for defining a content filter
 * @package CMS
 * @internal
 * @ignore
 * @final
 * @author Robert Campbell
 *
 */
final class ContentListFilter
{
	const EXPR_OWNER = 'OWNER_UID';
	const EXPR_EDITOR = 'EDITOR_UID';
	const EXPR_TEMPLATE = 'TEMPLATE_ID';
	const EXPR_DESIGN = 'DESIGN_ID';

	private $_type;
	private $_expr; // string

	/**
	 *
	 * @param mixed $key
	 * @return mixed
	 * @throws InvalidArgumentException upon unrecognized $key
	 */
	public function __get($key)
	{
		switch( $key ) {
		case 'type':
		case 'expr':
			$key = '_'.$key;
			return $this->$key;

		default:
			throw new InvalidArgumentException("$key is not a gettable member of ".self::class);
		}
	}

	/**
	 *
	 * @param mixed $key, normally a string
	 * @param mixed $val, normally a string
	 * @throws InvalidArgumentException upon unrecognized $key or $val
	 */
	public function __set($key,$val)
	{
		switch( $key ) {
		case 'type':
			switch( $val ) {
			case self::EXPR_OWNER:
			case self::EXPR_EDITOR:
			case self::EXPR_TEMPLATE:
			case self::EXPR_DESIGN:
				$this->_type = $val;
				break;
			default:
				throw new InvalidArgumentException("$val is an invalid type for ".self::class);
			}
			break;

		case 'expr':
			$this->_expr = trim($val);
			break;

		default:
			throw new InvalidArgumentException("$key is not a settable member of ".self::class);
		}
	}
} // class
