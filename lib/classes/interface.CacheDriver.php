<?php
# The interface for interacting with cache drivers
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

namespace CMSMS;

/**
 * An interface for interacting with cache drivers
 *
 * @since 2.0
 * @author Robert Campbell
 * @package CMS
 * @license GPL
 */
interface CacheDriver
{
  /**
   * Clear all cached values in a group.
   * If the $group parameter is not specified, use the current group
   *
   * @param string $group
   */
  public function clear($group = '');

  /**
   * Retrieve a cached value
   * If the $group parameter is not specified, use the current group
   *
   * @param string $key
   * @param string $group
   * @return mixed
   */
  public function get($key,$group = '');

  /**
   * Test if a cached value exists
   * If the $group parameter is not specified, use the current group
   *
   * @param string $key
   * @param string $group
   * @return bool
   */
  public function exists($key,$group = '');

  /**
   * Erase a cached value.
   * If the $group parameter is not specified, use the current group
   *
   * @param string $key
   * @param string $group
   */
  public function erase($key,$group = '');

  /**
   * Add a cached value
   * If the $group parameter is not specified, use the current group
   *
   * @param string $key
   * @param mixed  $value
   * @param string $group
   */
  public function set($key,$value,$group = '');

  /**
   * Set A current group
   *
   * @param string $group
   */
  public function set_group($group);
} // interface