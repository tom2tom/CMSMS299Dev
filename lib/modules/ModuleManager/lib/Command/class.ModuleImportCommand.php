<?php
# ModuleManager class: ..
# Copyright (C) 2017-2018 Robert Campbell <calguy1000@cmsmadesimple.org>
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
use CMSMS\HookManager;
use CMSMS\ModuleOperations;
use GetOpt\Operand;
use RuntimeException;
use function audit;

class ModuleImportCommand extends Command
{
    public function __construct( App $app )
    {
        parent::__construct( $app, 'moma-import' );
        $this->setDescription('Import a module XML file into CMSMS');
        $this->addOperand( new Operand( 'filename', Operand::REQUIRED ) );
    }

    public function handle()
    {
        $moma = cms_utils::get_module('ModuleManager');
        $ops = ModuleOperations::get_instance();
        $filename = $this->getOperand('filename')->value();
        if( !is_file( $filename) ) throw new RuntimeException("Could not find $filename to import");

        HookManager::do_hook('ModuleManager::BeforeModuleImport', [ 'file'=>$filename ] );
        $moma->get_operations()->expand_xml_package( $filename, true, false );
        HookManager::do_hook('ModuleManager::AfterModuleImport', [ 'file'=>$filename ] );

        audit('',$moma->GetName(),'Imported Module from '.$filename);
        echo "Imported: $filename\n";
    }
} // class
