<?php
# ModuleManager class: ReposGetXMLCommand
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
use CMSMS\Utils;
use GetOpt\Operand;
use ModuleManager\modulerep_client;
use ModuleNoDataException;
use RemoteAdmin\CLI\GetOptExt\Command;
use RemoteAdmin\CLI\GetOptExt\GetOpt;
use RemoteAdmin\CLI\GetOptExt\Option;
use RuntimeException;

class ReposGetXMLCommand extends Command
{
    public function __construct( App $app )
    {
        parent::__construct( $app, 'moma-repos-getxml' );
        $this->addOperand( new Operand( 'module', Operand::REQUIRED ) );
        $this->addOption( Option::create(null,'xmlver', GetOpt::REQUIRED_ARGUMENT )->setDescription('Download a specific version') );
    }

    protected function getShortDescription()
    {
        return 'Download a module XML file from the repository';
    }

    protected function getLongDescription()
    {
        return <<<EOT
This command will download a single module XML file to the local machine for later import.  No dependency checks are performed.
By default this command will download the last released version of the specified module.
EOT;
    }

    public function handle()
    {
        try {
            $moma = Utils::get_module('ModuleManager');
            $module = $this->getOperand('module')->value();

            $data = modulerep_client::get_modulelatest( [ $module ] );
            if( !$data ) throw new RuntimeException("Module $module not found in repository");
            if( count($data) > 1 ) throw new RuntimeException('Internal error: multiple results returned');
            $data = $data[0];

            $filename = $data['filename'];
            $md5sum = $data['md5sum'];
            $tmpfile = modulerep_client::get_repository_xml( $filename );
            if( !$tmpfile ) throw new RuntimeException('Problem downloading '.$filename.' no data returned');
            $newsum = md5_file( $tmpfile );
            if( $md5sum != $newsum ) throw new RuntimeException("Problem downloading $filename, checksum fail");

            copy( $tmpfile, $filename );
            unlink( $tmpfile );
            echo $filename."\n";
        }
        catch( ModuleNoDataException $e ) {
            throw new RuntimeException("Module $module not found in repository");
        }

    }
} // class
