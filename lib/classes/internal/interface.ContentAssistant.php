<?php
# Interface for a content assistant
# Copyright (C) 2016-2018 Rovert Campbell <calguy1000@cmsmadesimple.org>
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

namespace CMSMS\internal;

/**
 * An interface for a content assistant.
 *
 * ContentAssistant classes provide various extensions and utilities for content
 * objects.
 *
 * @since		2.0
 * @author		calguy1000
 * @abstract
 * @package		CMS
 */
interface ContentAssistant {

  /**
   * Construct a ContentAssistant object
   *
   * @abstract
   * @param ContentBase Specify the object that we are building an assistant for.
   */
  public function __construct(ContentBase $content);
} // interface
