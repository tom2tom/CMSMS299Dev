<?php
namespace cms_installer\wizard {
use cms_installer\request;
use cms_installer\session;
use DirectoryIterator;
use Exception;
use RegexIterator;

final class wizard
{
    const STATUS_OK = 'OK';
    const STATUS_ERROR = 'ERROR';
    const STATUS_BACK = 'BACK';
    const STATUS_NEXT = 'NEXT';

    // __DIR__ might be phar://abspath/to/pharfile/relpath/to/thisfolder
    public static $_classdir = __DIR__;
    public static $_namespace = __NAMESPACE__;

    private static $_instance = null;
    private static $_initialized = false; //blocker for re-init's
    private static $_stepvar = 's';
    private static $_steps;
    private static $_stepobj;

    /**
     * @ignore
     */
    #[\ReturnTypeWillChange]
    private function __construct()
    {
    }

    /**
     * @ignore
     */
    #[\ReturnTypeWillChange]
    private function __clone()
    {
    }

    /**
     * Get the singleton instance of this class
     * This class is accessed many times during an installer-run, efficiency
     * favours just one instance.
     *
     * @param string $classdir Optional file-path of folder containing alternative step classes
     * @param string $namespace Optional namespace of alternative step classes
     * @return self
     * @throws Exception if supplied $classdir is invalid
     */
    public static function get_instance($classdir = '', $namespace = '') : self
    {
        if (!self::$_instance) {
            self::$_instance = new self();
            if ($classdir) {
                $classdir = rtrim($classdir, ' \/');
                if (!is_dir($classdir)) {
                    throw new Exception('Invalid wizard directory '.$classdir);
                }
                $me = self::$_instance;
                $me::$_classdir = $classdir;
//              $me::$_name = basename($classdir);
            }
//          else { $me =self::$_instance; $me::$_name = basename(__DIR__); }
            if ($namespace) {
                if (!isset($me)) {
                    $me = self::$_instance;
                }
                $me::$_namespace = $namespace;
            }
        }
        return self::$_instance;
    }

    public function get_nav()
    {
        $this->_init();
        return self::$_steps;
    }

    public function get_step_var()
    {
        return self::$_stepvar;
    }

    public function set_step_var($str)
    {
        if ($str) {
            self::$_stepvar = $str;
        }
    }

    public function cur_step() : int
    {
        //TODO security check per $this->get_data(self::$_stepvar)
        if (self::$_stepvar && isset($_GET[self::$_stepvar])) {
            $val = (int)$_GET[self::$_stepvar];
        } else {
            $val = 1;
        }
        return $val;
    }

    public function finished() : bool
    {
        $this->_init();
        return $this->cur_step() > $this->num_steps();
    }

    public function num_steps() : int
    {
        $this->_init();
        return count(self::$_steps);
    }

    public function get_step()
    {
        if (is_object(self::$_stepobj)) {
            return self::$_stepobj;
        }

        $this->_init();
        $rec = self::$_steps[$this->cur_step()];
        if (!class_exists($rec['class'])) {
            require_once self::$_classdir.DIRECTORY_SEPARATOR.$rec['fn'];
        }
        $obj = new $rec['class']();
        if (is_object($obj)) {
            self::$_stepobj = $obj;
            return $obj;
        }
    }

    // effectively installer_base::get_config()[$key] with a default
    public function get_data($key, $dflt = null)
    {
        $sess = session::get_instance();
        return $sess[$key] ?? $dflt;
    }

    // this replicates installer_base::set_config_val()
    public function set_data($key, $value)
    {
        $sess = session::get_instance();
        $sess[$key] = $value;
    }

    // this replicates installer_base::merge_config_vals()
    public function merge_data($key, $valsarray)
    {
        $sess = session::get_instance();
        $current = $sess[$key] ?? [];
        if (!is_array($current)) {
            $current = [$key => $current];
        }
        $sess[$key] = $valsarray + $current;
    }

    // this replicates installer_base::remove_config_val()
    public function clear_data($key)
    {
        $sess = session::get_instance();
        if (isset($sess[$key])) {
            unset($sess[$key]);
        }
    }

    /**
     * @return mixed output from the current step | null
     */
    public function process()
    {
        $this->_init();
        $obj = $this->get_step();
        return ($obj) ? $obj->run() : null;
    }

    /**
     * Get the url of the specified step index
     * @param mixed $idx numeric 1 .. no. of steps
     * @return string
     */
    public function step_url($idx) : string
    {
        $this->_init();

        $idx = (int)$idx;
        if ($idx < 1 || $idx > $this->num_steps()) {
            return '';
        }

        $request = request::get_instance();
        $url = $request->raw_server('REQUEST_URI');
        $urlmain = explode('?', $url);
        $url = $urlmain[0].'?'.self::$_stepvar.'='.$idx;
        $url .= '&'.chr(random_int(97, 122)).bin2hex(random_bytes(3)).'='.chr($idx + 70); //sorta nonce
        $this->set_data(self::$_stepvar, $url); //for security check when we get there
        return $url;
    }

    /**
     * Get the url of the next step in numeric order
     * @return string
     */
    public function next_url() : string
    {
        $this->_init();

        $idx = $this->cur_step() + 1;
        if ($idx > $this->num_steps()) {
            return '';
        }
        $request = request::get_instance();
        $url = $request->raw_server('REQUEST_URI'); // TODO if N/A in $_SERVER
        $urlmain = explode('?', $url);
        $url = $urlmain[0].'?'.self::$_stepvar.'='.$idx;
        $url .= '&'.chr(random_int(97, 122)).bin2hex(random_bytes(3)).chr($idx + 70); //sorta nonce
        $this->set_data(self::$_stepvar, $url); //for security check
        return $url;
    }

    /**
     * Get the url of the previous step in numeric order
     * @return string
     */
    public function prev_url() : string
    {
        $this->_init();

        $idx = $this->cur_step() - 1;
        if ($idx <= 0) {
            return '';
        }

        $request = request::get_instance();
        $url = $request->raw_server('REQUEST_URI');
        $urlmain = explode('?', $url);
        $url = $urlmain[0].'?'.self::$_stepvar.'='.$idx;
        $url .= '&'.chr(random_int(97, 122)).bin2hex(random_bytes(3)).chr($idx + 70); //sorta nonce
        $this->set_data(self::$_stepvar, $url); //for security check
        return $url;
    }

    /**
     * One-time setup
     * @throws Exception
     */
    private function _init()
    {
        if (self::$_initialized) {
            return;
        }

        // find all step-classes in the wizard directory (not recursive) (intra-phar globbing N/A)
        $iter = new RegexIterator(
            new DirectoryIterator(self::$_classdir),
            '/^class\.wizard_step\d+\.php$/'
        );

        $s = self::cur_step();
        $_data = [];
        foreach ($iter as $inf) {
            $filename = $inf->getFilename();
            $tmp = substr($filename, 0, -4);
            $classname = substr($tmp, 6);
            $fc = (self::$_namespace) ? self::$_namespace.'\\'.$classname : $classname;
            $idx = (int)substr($classname, 11);
            $a = ($idx == $s) ? 1 : 0;
            $_data[$idx] = [
                'fn' => $filename,
                'class' => $fc,
                'classname' => $classname,
                'description' => '',
                'active' => $a,
                'aside' => 1,
            ];
        }
        if (!$_data) {
            throw new Exception('Could not find wizard steps in '.self::$_classdir);
        }
        ksort($_data, SORT_NUMERIC);
        // final (completion-notice) step not shown in the aside
        $s = array_keys($_data)[count($_data) - 1]; // PHP7.3+ array_key_last($_data)
        $_data[$s]['aside'] = 0;

        self::$_steps = $_data;
        self::$_initialized = true;
    }
} // class
} // wizard namespace
namespace {
use cms_installer\wizard\wizard;

// functions to generate GUI-installer messages (formerly in misc.functions.php)

function verbose_msg(string $str)
{
    $obj = wizard::get_instance()->get_step();
    if (method_exists($obj, 'verbose')) {
        $obj->verbose($str);
    }
}

function status_msg(string $str)
{
    $obj = wizard::get_instance()->get_step();
    if (method_exists($obj, 'message')) {
        $obj->message($str);
    }
}

function error_msg(string $str)
{
    $obj = wizard::get_instance()->get_step();
    if (method_exists($obj, 'error')) {
        $obj->error($str);
    }
}
} //global namespace
