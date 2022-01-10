<?php
/*
An interface to define methods in light modules.
Copyright (C) 2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
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
 * @since 2.99
 * @package CMS
 * @license GPL
 *
 * NOTE some significant API differences -
1. In module-actions for IResource 'light' modules:
  $this is a CMSMS\ResourceMethods object and the related module-instance is $this->mod
  c.f. for normal-module actions:
  the related module is $this
2. Getting a smarty-template object in module-actions for light modules:
  $tpl = $this->GetTemplateObject('some.tpl');
  c.f. for normal-module actions:
  $tpl = $smarty->createTemplate($this->GetTemplateResource('some.tpl'));
 */
interface IResource
{
    public function __call(string $name, array $args);
    public function GetAdminDescription(); // subject to HasAdmin(), admin menu-item tooltip etc
    public function GetAdminSection(); // subject to HasAdmin(), admin menu-item section name, or '' if no menu-items
    public function GetChangeLog(); // module-manager: display changelog NOTE GetAbout() in long-module
    public function GetFriendlyName(); // admin menu-item label etc
    public function GetHelp(); // module-manager: display help NOTE GetHelpPage() in long-module
    public function GetVersion();
    public function HasAdmin(); // whether the module has an admin UI
    public function MinimumCMSVersion();
    public function VisibleToAdminUser(); // subject to HasAdmin(), whether the module supports an admin UI for the current user
}
