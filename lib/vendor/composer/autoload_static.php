<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInita781aa22c39a2bd61bd0eac8e4232662
{
    public static $files = array();

    public static $prefixLengthsPsr4 = array();

    public static $prefixDirsPsr4 = array();

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInita781aa22c39a2bd61bd0eac8e4232662::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInita781aa22c39a2bd61bd0eac8e4232662::$prefixDirsPsr4;

        }, null, ClassLoader::class);
    }
}
