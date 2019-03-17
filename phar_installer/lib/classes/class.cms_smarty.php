<?php

namespace cms_installer;

use cms_installer\langtools;
use Exception;
use Smarty;
use function cms_installer\get_app;

final class cms_smarty extends Smarty
{
    private static $_instance;

    public function __construct()
    {
        parent::__construct();

        $app = get_app();
//        $rootdir = $app->get_rootdir();
        $tmpdir = $app->get_tmpdir().'/m'.md5(__FILE__);
        $assdir = $app->get_assetsdir();
//        $basedir = dirname(__DIR__,2);

        $this->setTemplateDir($assdir.'/templates');
        $this->setConfigDir($assdir.'/configs');
        $this->setCompileDir($tmpdir.'/templates_c');
        $this->setCacheDir($tmpdir.'/cache');

        $this->registerPlugin('modifier','tr',[$this,'modifier_tr']);
        $dirs = [$this->compile_dir,$this->cache_dir];
        for( $i = 0, $n = count($dirs); $i < $n; $i++ ) {
            @mkdir($dirs[$i],0771,TRUE);
            if( !is_dir($dirs[$i]) ) throw new Exception('Required directory '.$dirs[$i].' does not exist');
        }
    }

    public static function get_instance()
    {
        if( !is_object(self::$_instance) ) self::$_instance = new self();
        return self::$_instance;
    }

    public function modifier_tr(...$args)
    {
        return langtools::get_instance()->translate($args);
    }
}
