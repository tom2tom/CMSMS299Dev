<?php
# ModuleManager class: ReposDependsCommand
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
use GetOpt\Operand;
use ModuleManager\modulerep_client;
use ModuleNoDataException;
use RuntimeException;

class ReposDependsCommand extends Command
{
    public function __construct( App $app )
    {
        parent::__construct( $app, 'moma-repos-xmldepends' );
        $this->addOperand( new Operand( 'filename', Operand::REQUIRED ) );
    }

    protected function getShortDescription()
    {
        return 'List the dependencies of a specific repository XML file';
    }

    protected function getLongDescription()
    {
        return 'This command will return a list of all dependencies of a specific repository XML file';
    }

    public function handle()
    {
        try {
            $filename = $this->getOperand('filename')->value();

            $data = modulerep_client::get_module_depends( $filename );
            if( !$data || !is_array($data) || !$data[0] ) throw new RuntimeException('File not found');

            $data = $data[1];
            foreach( $data as $dep ) {
                printf("%s (%s)\n",$dep['name'],$dep['version']);
            }
        }
        catch( ModuleNoDataException $e ) {
            throw new RuntimeException("Module $module not found in repository");
        }
    }
} // class
