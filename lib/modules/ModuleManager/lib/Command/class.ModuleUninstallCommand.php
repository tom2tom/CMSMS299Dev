<?php
# ModuleManager class: ..
# Copyright (C) 2017-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

namespace ModuleManager\Command;

use CMSMS\CLI\App;
use CMSMS\CLI\GetOptExt\Command;
use CMSMS\ModuleOperations;
use CMSMS\Utils;
use GetOpt\Operand;
use ModuleManager\module_info;
use RuntimeException;

class ModuleUninstallCommand extends Command
{
    public function __construct( App $app )
    {
        parent::__construct( $app, 'moma-uninstall' );
        $this->setDescription('Uninstall a module... this does not remove module files, but will potentially clear all module data');
        $this->addOperand( new Operand( 'module', Operand::REQUIRED ) );
    }

    public function handle()
    {
        $ops = ModuleOperations::get_instance();
        $moma = Utils::get_module('ModuleManager');
        $module = $this->getOperand('module')->value();
        $info = new module_info( $module );
        if( !$info['dir'] ) throw new RuntimeException('Nothing is known about module '.$module);
        if( !$info['installed'] ) throw new RuntimeException("module $module is not installed");

        $instance = $ops->get_module_instance($module);
        if( !is_object( $instance ) ) throw new RuntimeException('Could not instantiate module '.$module);

        $result = $ops->UninstallModule($module);
        if( $result[0] == FALSE ) throw new RuntimeException($result[1]);

        echo "Uninstalled module $module\n";
    }
} // class
