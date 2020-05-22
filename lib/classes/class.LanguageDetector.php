<?php
#Translation functions/classes
#Copyright (C) 2014-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
#Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.
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

use CMSMS\NlsOperations;

/**
 * An abstract class for determining a suitable language for display.
 * A derived class might be used by CMSMS on frontend requests to detect a
 * suitable language. Modules (one-only per request) may specify an alternative
 * derived class, presumably with a different methodology e.g. interpret preferences etc
 *
 * @see NlsOperations::set_language_detector()
 * @author Robert Campbell
 * @package CMS
 * @license GPL
 * @since 2.9
 * @since 1.11 as global-namespace CmsLanguageDetector
 */
abstract class LanguageDetector
{
  /**
   * Abstract function to determine a language.
   * This method might use cookies, session data, user preferences,
   * values from the URL or from the browser to determine a language.
   * That language must be recognized by / exist on the installed CMSMS system.
   *
   * @return string language name
   */
  abstract public function find_language();
}
