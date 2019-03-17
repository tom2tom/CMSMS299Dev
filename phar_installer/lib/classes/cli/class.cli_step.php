<?php

namespace cms_installer\cli;

abstract class cli_step
{
    private $_app;

    public function __construct( cms_cli_install $app )
    {
        $this->_app = $app;
    }

    protected function app() { return $this->_app; }
    abstract function run();
} // class
