<?php

namespace cms_installer\wizard;

use cms_installer\request;
use Exception;
use function cms_installer\get_app;
use function cms_installer\lang;
use function cms_installer\smarty;
use function cms_installer\specialize;

class wizard_step
{
    public static $_registered = 0;

    public function __construct()
    {
        $app = get_app();
        $dir = $app->get_destdir();
        if( !$dir ) throw new Exception('Session Failure');

        $smarty = smarty();

        if( !self::$_registered ) {
            $smarty->registerPlugin('function','wizard_form_start', [$this,'fn_wizard_form_start'])
             ->registerPlugin('function','wizard_form_end', [$this,'fn_wizard_form_end']);
            self::$_registered = 1;
        }

        $smarty->assign('version',$app->get_dest_version())
         ->assign('version_name',$app->get_dest_name())
         ->assign('dir',$dir)
         ->assign('in_phar',$app->in_phar())
         ->assign('cur_step',$this->cur_step());
    }

    public function get_name()
    {
        return get_class($this);
    }

    /**
     * @abstract
     */
    public function get_description()
    {
        return null;
    }

    public function get_wizard()
    {
        return wizard::get_instance();
    }

    public function cur_step()
    {
        return wizard::get_instance()->cur_step();
    }

    public function run()
    {
        $request = request::get_instance();
        if( $request->is_post() ) $res = $this->process();
        $this->display();
        return wizard::STATUS_OK;
    }

    /**
     * Process the results of this step's form (POST only)
     * @abstract
     */
    protected function process() {}

    /**
     * Display information for this step
     */
    protected function display()
    {
//      $app = get_app();
        $smarty = smarty();
        $smarty->assign('wizard_steps',$this->get_wizard()->get_nav());
        $smarty->assign('title',$this->get_primary_title());
    }

    protected function finish()
    {
        echo '<script type="text/javascript">finish();</script>'.PHP_EOL;
        flush();
    }

    public function fn_wizard_form_start($params, $smarty)
    {
        echo '<form method="POST" action="'.$_SERVER['REQUEST_URI'].'">';
    }

    public function fn_wizard_form_end($params, $smarty)
    {
        echo '</form>';
    }

    protected function get_primary_title()
    {
        $app = get_app();
        $action = $this->get_wizard()->get_data('action');
        $str = null;
        switch( $action ) {
            case 'upgrade':
                $str = lang('action_upgrade',$app->get_dest_version());
                break;
            case 'freshen':
                $str = lang('action_freshen',$app->get_dest_version());
                break;
//          case 'install':
            default:
                $str = lang('action_install',$app->get_dest_version());
        }
        return $str;
    }

    public function set_block_html($id,$html)
    {
        $html = addslashes($html);
        echo '<script type="text/javascript">set_block_html(\''.$id.'\',\''.$html.'\');</script>'.PHP_EOL;
        flush();
    }

    public function message($msg)
    {
        $msg = addslashes($msg);
        echo '<script type="text/javascript">add_message(\''.$msg.'\');</script>'.PHP_EOL;
        flush();
    }

    public function error($msg)
    {
        $msg = addslashes($msg);
        echo '<script type="text/javascript">add_error(\''.$msg.'\');</script>'.PHP_EOL;
        flush();
    }

    public function verbose($msg)
    {
        $config = get_app()->get_config();
        $verbose = $config['verbose'] ?? false;
        if( $verbose ) {
            $msg = addslashes($msg);
            echo '<script type="text/javascript">add_verbose(\''.$msg.'\');</script>'.PHP_EOL;
            flush();
        }
    }
}
