<?php
/*
An interface to define methods in light modules.
Copyright (C) 2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
You should have received a copy of the GNU General Public License
along with this program. If not, see <https://www.gnu.org/licenses/>.
*/

namespace CMSMS;

/**
 * An interface to define the mandatory methods in 'light' modules.
 * These are a small subset of methods in the CMSModule class,
 * essentially for class administration.
 * Some additional methods are available if needed, after on-demand
 * inclusion of class.ResourceMethods.php
 * @see CMSModule
 * @see ResourceMethods
 * @since 2.9
 * @package CMS
 * @license GPL
 */
interface IResource
{
    public function __call($name, $args);
    public function GetAbout(); // module-manager: display changelog 
    public function GetAdminDescription(); // admin menu-item tooltip etc
    public function GetAdminSection(); // admin menu-item section name, or ''
    public function GetDependencies(); // array
    public function GetFriendlyName(); // admin menu-item label etc
    public function GetHelpPage(); // module-manager: display help 
    public function GetName(); // module (private) name
    public function GetVersion();
    public function HasAdmin(); // whether the module has an admin UI
    public function HasCapability($capability, $params);
    public function InstallPostMessage(); // advice about things to follow up on 
    public function MinimumCMSVersion();
    public function VisibleToAdminUser(); // whether the module has an admin UI for the current user
}
