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
use RuntimeException;

class PingModuleServerCommand extends Command
{
    public function __construct( App $app )
    {
        parent::__construct( $app, 'moma-ping' );
        $this->setDescription('Ping the module server, and test for connectivity');
    }

    public function handle()
    {
        $connection_ok = utils::is_connection_ok();
        if( !$connection_ok ) throw new RuntimeException('A problem occurred communicating with the module server');
    }
} // class
