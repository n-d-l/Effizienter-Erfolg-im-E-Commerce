<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit741b778ae69e41b9f121ff1e2ac8f6d0
{
    public static $prefixLengthsPsr4 = array (
        'S' => 
        array (
            'SepaQr\\' => 7,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'SepaQr\\' => 
        array (
            0 => __DIR__ . '/..' . '/smhg/sepa-qr-data/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit741b778ae69e41b9f121ff1e2ac8f6d0::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit741b778ae69e41b9f121ff1e2ac8f6d0::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit741b778ae69e41b9f121ff1e2ac8f6d0::$classMap;

        }, null, ClassLoader::class);
    }
}
