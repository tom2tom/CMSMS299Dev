<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInita781aa22c39a2bd61bd0eac8e4232662
{
    public static $files = array (
        'f084d01b0a599f67676cffef638aa95b' => __DIR__ . '/..' . '/smarty/smarty/libs/bootstrap.php',
    );

    public static $prefixLengthsPsr4 = array (
        'P' => 
        array (
            'PHPMailer\\PHPMailer\\' => 20,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'PHPMailer\\PHPMailer\\' => 
        array (
            0 => __DIR__ . '/..' . '/phpmailer/phpmailer/src',
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInita781aa22c39a2bd61bd0eac8e4232662::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInita781aa22c39a2bd61bd0eac8e4232662::$prefixDirsPsr4;

        }, null, ClassLoader::class);
    }
}
