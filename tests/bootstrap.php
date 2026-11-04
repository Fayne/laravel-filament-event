<?php

use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\PackageManifest;

putenv('COMPOSER_VENDOR_DIR=' . dirname(__DIR__) . '/vendor');
$_ENV['COMPOSER_VENDOR_DIR'] = dirname(__DIR__) . '/vendor';

$loader = require dirname(__DIR__) . '/vendor/autoload.php';
$loader->addPsr4('Fayne\\CMS\\', dirname(__DIR__, 2) . '/laravel-filament-cms-core/src', true);

$skeleton = dirname(__DIR__) . '/vendor/orchestra/testbench-core/laravel';
(new PackageManifest(new Filesystem, $skeleton, $skeleton . '/bootstrap/cache/packages.php'))->build();
