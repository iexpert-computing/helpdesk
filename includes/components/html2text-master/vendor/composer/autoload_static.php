<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInite3152c247153ddf5aa9195327a822382
{
    public static $prefixLengthsPsr4 = array (
        'H' => 
        array (
            'Html2Text\\' => 10,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Html2Text\\' => 
        array (
            0 => __DIR__ . '/..' . '/html2text/html2text/src',
            1 => __DIR__ . '/..' . '/html2text/html2text/test',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInite3152c247153ddf5aa9195327a822382::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInite3152c247153ddf5aa9195327a822382::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInite3152c247153ddf5aa9195327a822382::$classMap;

        }, null, ClassLoader::class);
    }
}
