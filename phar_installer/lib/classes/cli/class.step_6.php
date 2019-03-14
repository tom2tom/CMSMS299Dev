<?php

namespace cms_installer\cli;

use cms_installer\cli\cli_step;
use console; //TODO
use function cms_installer\lang;

class step_6 extends cli_step
{
    protected function ask_questions( $console )
    {
        $languages = $this->app()->get_language_list();
        unset($languages['en_US']);
        foreach( $languages as $key => &$val ) {
            $val = html_entity_decode( $val );
        }

        $console->show( lang('cli_hdr_languages') )->lf();
        $langs = null;

        while( 1 ) {
            $langs = $console->ask_string_cb(
                function( $val, $console ) {
                    $console->show('Enter a list of additional language codes to install','bold')->lf();
                    $console->show('Enter * to install all languages, or "list" to display a list')->lf();
                    $console->show('> Additional Languages: ');
                }, $langs);
            echo "DEBUG: $langs\n"; die();
        }
    }

    public function run()
    {
        // ask site info, and languages
        $op = $this->app()->get_op();
        if( $this->app()->is_interactive() ) {
            if( $op == 'install' ) {
                $console = new console();
                $console->clear();
                $console->show_centered(lang('cli_welcome', 'bold+underlind()'))->lf();;
                $console->show_centered(lang('cli_cmsver', $this->app()->get_dest_version()), 'bold' )->lf();
                $console->show_centered(lang('cli_hdr_op', $this->app()->get_op(), $this->app()->get_destdir()))->lf();
                $console->show_centered('----')->lf()->lf();

                $this->ask_questions($console);
            } // install
        } // interactive
    }
} // class
