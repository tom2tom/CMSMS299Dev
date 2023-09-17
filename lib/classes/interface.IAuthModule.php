<?php
/*
An interface to define the minimum API for admin-console authentication modules.
Copyright (C) 2019-2023 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

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

/**
 * An interface to define methods in admin-console authentication modules.
 * @since 3.0
 * @package CMS
 * @license GPL
 */
interface IAuthModule
{
   /**
     * Process the current login 'phase', and generate appropriate
     * page-content and ancillary parameters
     *
     * @return array with members representing login-form content and related parameters
     */
    public function fetch_login_panel(): array;

    /**
     * Perform the entire login process without theme involvement
     */
    public function display_login_page();
} // interface
