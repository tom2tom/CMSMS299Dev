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

use cms_utils;
use CMSMS\CLI\App;
use CMSMS\CLI\GetOptExt\Command;
use CMSMS\ModuleOperations;
use GetOpt\Operand;
use ModuleManager\module_info;
use RuntimeException;
use function audit;
use function recursive_delete;

class ModuleRemoveCommand extends Command
{
    public function __construct( App $app )
    {
        parent::__construct( $app, 'moma-remove' );
        $this->setDescription('Remove an uninstalled module from the filesystem');
        $this->addOperand( new Operand( 'module', Operand::REQUIRED ) );
    }

    public function handle()
    {
        $ops = ModuleOperations::get_instance();
        $moma = cms_utils::get_module('ModuleManager');
        $module = $this->getOperand('module')->value();
        $info = new module_info( $module );

        if( !$info['dir'] ) throw new RuntimeException('Nothing is known about module '.$module);
        if( $info['installed'] ) throw new RuntimeException('Cannot remove module '.$module.' because it is installed');

        $result = recursive_delete( $info['dir'] );
        if( !$result ) throw new RuntimeException('Error removing module '.$module);

        audit('',$moma->GetName(),'Removed module '.$module);
        echo "Removed module $module\n";
    }
} // class
