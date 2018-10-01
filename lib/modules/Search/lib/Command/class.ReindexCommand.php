<?php
namespace Search\Command;

use CMSMS\CLI\App;
use CMSMS\CLI\GetOptExt\Command;
use cms_utils;

class ReindexCommand extends Command
{
    public function __construct( App $app )
    {
        parent::__construct( $app, 'search-reindex' );
        $this->setDescription('Re-index all search content');
    }

    public function handle()
    {
        $mod = cms_utils::get_module('Search');
        $mod->Reindex();
    }
} // class
