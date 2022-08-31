<?php

use Nadar\PhpComposerReader\ComposerReader;
use App\Controllers\API;

require __DIR__ . '/../vendor/autoload.php';

$settings = require __DIR__ . '/../config.php';

if (php_sapi_name() === 'cli') {
    $composer = (new ComposerReader(__DIR__ . '/../composer.json'))->getContent();
    $app = new Ahc\Cli\Application($composer['description'], "v{$composer['version']}");

    foreach (glob(__DIR__ . '/Console/*Command.php') as $file) {
        require_once($file);
        $file = basename($file, '.php');
        $file = "\\App\\Console\\{$file}";
        $app->add(new $file);
    }

    $app->handle($_SERVER['argv']);
    exit;
}

$app = new API($settings);
