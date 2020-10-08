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
use CMSMS\LangOperations;
use CMSMS\ModuleOperations;
use CMSMS\Utils;
use GetOpt\Operand;
use RuntimeException;
use function audit;

class ModuleInstallCommand extends Command
{
    public function __construct( App $app )
    {
        parent::__construct( $app, 'moma-install' );
        $this->setDescription('Install a module that is known, but not installed');
        $this->addOperand( new Operand( 'module', Operand::REQUIRED ) );
    }

    public function handle()
    {
        $ops = ModuleOperations::get_instance();
        $moma = Utils::get_module('ModuleManager');
        $module = $this->getOperand('module')->value();

        LangOperations::allow_nonadmin_lang(TRUE);
        $ops = ModuleOperations::get_instance();
        $result = $ops->InstallModule($module);
        if( !is_array($result) || !isset($result[0]) ) throw new RuntimeException('Module installation failed');
        if( $result[0] == FALSE ) throw new RuntimeException($result[1]);

        $modinstance = $ops->get_module_instance($module,'',TRUE);
        if( !is_object($modinstance) ) throw new RuntimeException('Problem instantiating module '.$module);

        audit('',$moma->GetName(),'Installed '.$modinstance->GetName().' '.$modinstance->GetVersion());
        echo 'Installed: '.$modinstance->GetName().' '.$modinstance->GetVersion()."\n";
    }
} // class
