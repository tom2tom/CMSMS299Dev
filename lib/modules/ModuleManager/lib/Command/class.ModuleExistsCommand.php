<?php
# ModuleManager class: ..
# Copyright (C) 2017-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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
use CMSMS\CLI\GetOptExt\Option;
use GetOpt\Operand;
use ModuleManager\ModuleInfo;

class ModuleExistsCommand extends Command
{
    public function __construct( App $app )
    {
        parent::__construct( $app, 'moma-exists' );
        $this->setDescription('Test if a module exists, or is known in any way');
        $this->addOption( Option::Create( 'v','verbose')->setDescription('Enable verbose mode') );
        $this->addOperand( new Operand( 'module', Operand::REQUIRED ) );
    }

    public function handle()
    {
        $verbose = $this->getOption( 'verbose' )->value();
        $module = $this->getOperand( 'module' )->value();

        $allmoduleinfo = ModuleInfo::get_all_module_info(TRUE);
        foreach( $allmoduleinfo as $one ) {
            if( $one['name'] == $module ) {
                // yep... we know about it.
                if( $verbose ) echo "$module is known\n";
                return;
            }
        }

        if( $verbose ) echo "$module is NOT known\n";
        exit(1);
    }
} // class
