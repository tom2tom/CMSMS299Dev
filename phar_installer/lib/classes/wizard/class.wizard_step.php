<?php
namespace cms_installer\wizard;

use cms_installer\request;
use Exception;
use const PHP_EOL;
use function cms_installer\get_app;
use function cms_installer\lang;
use function cms_installer\smarty;

//use function cms_installer\specialize;

class wizard_step
{
    // default installer bug-report URL @ CMSMS forge for core
    const FORGE_URL = 'http://dev.cmsmadesimple.org/bug/list/6';
    protected $forge_url;
    protected static $_langdir = 'ltr';

    private static $_registered = 0;

    public function __construct()
    {
        $app = get_app();
        $dir = $app->get_destdir();
        if (!$dir) {
            throw new Exception('Session Failure');
        }
        $config = $app->get_config();
        $this->forge_url = $config['bugurl'] ?? self::FORGE_URL;

        $smarty = smarty();

        if (!self::$_registered) {
            $smarty->registerPlugin('function', 'wizard_form_start', [$this, 'fn_wizard_form_start']);
            self::$_registered = 1;
        }

        $smarty->assign('version', $app->get_dest_version())
         ->assign('version_name', $app->get_dest_name())
         ->assign('dir', $dir)
         ->assign('lang_rtl', self::$_langdir == 'rtl')
         ->assign('in_phar', $app->in_phar())
         ->assign('cur_step', $this->cur_step());
    }

    public function get_name()
    {
        return get_called_class();
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
        if ($request->is_post()) {
            $res = $this->process();
        }
        $this->display();
        return wizard::STATUS_OK;
    }

    // Smarty plugin function. Form parameters are not supported.
    public function fn_wizard_form_start($params, $smarty)
    {
        echo '<form method="post" action="'.$_SERVER['REQUEST_URI'].'">';
    }

    // TODO replace the rubbish script uses (for dynamic page-content change|display)) in the following
    // Populate the page-element whose id is $id
    public function set_block_html($id, $html)
    {
        // escape for javascipt and html
        $html = addslashes($html);
        // append script to page content
        echo '<script type="text/javascript">set_block_html(\''.$id.'\', \''.$html.'\');</script>'.PHP_EOL;
        flush();
    }

    // Display $msg inside a styled <p/> appended to the element whose id is 'inner'
    public function message($msg)
    {
        $msg = addslashes($msg);
        echo '<script type="text/javascript">add_message(\''.$msg.'\');</script>'.PHP_EOL;
        flush();
    }

    // Display $msg inside a styled <p/> appended to the element whose id is 'inner'
    public function error($msg)
    {
        $msg = addslashes($msg);
        echo '<script type="text/javascript">add_error(\''.$msg.'\');</script>'.PHP_EOL;
        flush();
    }

    // If verbose mode applies, display $msg inside a <p/> appended to the element whose id is 'inner'
    public function verbose($msg)
    {
        $config = get_app()->get_config();
        $verbose = $config['verbose'] ?? false;
        if ($verbose) {
            $msg = addslashes($msg);
            echo '<script type="text/javascript">add_verbose(\''.$msg.'\');</script>'.PHP_EOL;
            flush();
        }
    }

    /**
     * Process the results of this step's form (POST only)
     * @abstract
     */
    protected function process()
    {
    }

    /**
     * Display information for this step
     * This method is to be called by each descendent-step's display()
     */
    protected function display()
    {
        $smarty = smarty();
        $smarty->assign('wizard_steps', $this->get_wizard()->get_nav());
        $smarty->assign('title', $this->get_primary_title());
    }

    protected function get_primary_title()
    {
        $app = get_app();
        $action = $this->get_wizard()->get_data('action');
        switch ($action) {
            case 'upgrade':
                $str = lang('action_upgrade', $app->get_dest_version());
                break;
            case 'freshen':
                $str = lang('action_freshen', $app->get_dest_version());
                break;
            case 'install':
                $str = lang('action_install', $app->get_dest_version());
                break;
            default:
                $str = '';
        }
        return $str;
    }

    protected function set_langdir($val)
    {
        $s = strtolower($val);
        if (!($s == 'rtl' || $s == 'ltr')) { $s = 'ltr'; }
        self::$_langdir = $s;
    }

    // Display the (<div/>) element whose id is 'bottom_nav', with $html included
    protected function alldone($html)
    {
        $html = json_encode($html);
        echo '<script type="text/javascript">alldone('.$html.');</script>'.PHP_EOL;
        flush();
    }

    // Display the (<div/>) element whose id is 'bottom_nav'
    protected function finish()
    {
        echo '<script type="text/javascript">finish();</script>'.PHP_EOL;
        flush();
    }
}
