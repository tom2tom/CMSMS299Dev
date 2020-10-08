<?php

namespace AdminLog\Command;

use AdminLog\storage;
use CMSMS\CLI\App;
use CMSMS\CLI\GetOptExt\Command;
use CMSMS\Utils;
use function audit;

class ClearLogCommand extends Command
{
    public function __construct( App $app )
    {
        parent::__construct( $app, 'adminlog-clear' );
        $this->setDescription('Clear admin log');
    }

    public function handle()
    {
        $mod = Utils::get_module('AdminLog');

        $storage = new storage( $mod );
        $storage->clear();
        audit('','Admin log','Cleared');
    }
}
