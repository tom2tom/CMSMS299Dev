<?php

// autoload_static.php @generated by Composer
// __DIR__ might be phar://abspath/to/pharfile/relpath/to/thisfolder

namespace Composer\Autoload;

class ComposerStaticInit761e0cd0b509946f5f906763270c2fa4
{
    public static $prefixLengthsPsr4 = array (
        'p' =>
        array (
            'passchk\\' => 8,
        ),
        'S' =>
        array (
            'StupidPass\\' => 11,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'passchk\\' =>
        array (
            0 => __DIR__ . '/../rumkin/passchk', // crappy workaround for data init
        ),
        'StupidPass\\' =>
        array (
            0 => __DIR__ . '/../northox/stupid-password',
        )
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit761e0cd0b509946f5f906763270c2fa4::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit761e0cd0b509946f5f906763270c2fa4::$prefixDirsPsr4;

        }, null, ClassLoader::class);
    }
}
