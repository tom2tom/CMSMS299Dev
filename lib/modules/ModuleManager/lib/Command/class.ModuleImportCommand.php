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
use CMSMS\Events;
use CMSMS\Utils;
use GetOpt\Operand;
use ModuleManager\operations;
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
        $filename = $this->getOperand('filename')->value();
        if( !is_file( $filename) ) throw new RuntimeException("Could not find $filename to import");

        $moma = Utils::get_module('ModuleManager');
        $ops = new operations($moma);
        Events::SendEvent( 'ModuleManager', 'BeforeModuleImport', [ 'file'=>$filename ] );
		try {
	        expand_xml_package( $filename, true, false );
		} catch (Exception $e) {
	        audit('',$moma->GetName(),'Module import failed: '.$filename,', '.$e->GetMessage());
			return;
		}
        Events::SendEvent( 'ModuleManager', 'AfterModuleImport', [ 'file'=>$filename ] );

        audit('',$moma->GetName(),'Imported module from '.$filename);
        echo "Imported: $filename\n";
    }
} // class
