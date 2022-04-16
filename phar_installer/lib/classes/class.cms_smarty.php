<?php
namespace cms_installer;

use cms_installer\langtools;
use Exception;
use Smarty;
use function cms_installer\get_app;
use function cms_installer\get_server_permissions;

final class cms_smarty extends Smarty
{
    private static $_instance;

    public function __construct()
    {
        parent::__construct();

        $assdir = dirname(__DIR__).DIRECTORY_SEPARATOR; // 'lib'-relative
        $this->setTemplateDir($assdir.'layouts');
        $this->setConfigDir($assdir.'configs');
        $app = get_app();
        $tmpdir = $app->get_tmpdir().DIRECTORY_SEPARATOR.'m'.md5(__FILE__).DIRECTORY_SEPARATOR;
        $this->setCompileDir($tmpdir.'templates_c');
        $this->setCacheDir($tmpdir.'cache');

        $this->registerPlugin('modifier', 'tr', [$this, 'modifier_tr']);
        $dirs = [$this->compile_dir, $this->cache_dir];
        $dirmode = get_server_permissions()[3]; // read+write+access

        for ($i = 0, $n = count($dirs); $i < $n; ++$i) {
            if (!is_dir($dirs[$i])) {
                mkdir($dirs[$i], $dirmode, true);
                if (!is_dir($dirs[$i])) {
                    throw new Exception('Required directory '.$dirs[$i].' does not exist');
                }
            }
        }
    }

    public static function get_instance() : self
    {
        if (!is_object(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function modifier_tr(...$args)
    {
        return langtools::get_instance()->translate(...$args);
    }
}
