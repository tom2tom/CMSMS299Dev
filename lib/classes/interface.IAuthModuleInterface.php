<?php
/*
An interface to define the minimum API for admin-console authentication modules.
Copyright (C) 2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
You should have received a copy of the GNU General Public License
along with this program. If not, see <https://www.gnu.org/licenses/>.
*/

namespace CMSMS;

/**
 * An interface to define methods in admin-console authentication modules.
 * @since 2.3
 * @package CMS
 * @license GPL
 */

interface IAuthModuleInterface
{
   /**
     * Process the current login 'phase', and generate appropriate page-content
     * for use upstream
     * No header / footer inclusions (js, css) are done (i.e. assumes upstream does that)
     * @return array including login-form content and related parameters
     */
    public function StageLogin() : array;

    /**
     * Perform the entire login process without theme involvement
     */
    public function RunLogin();
} // interface
