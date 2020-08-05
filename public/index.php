<?php

use BunnyCDN\Storage\PermaCache\Purge;
use BunnyCDN\Storage\PermaCache\Response;

require __DIR__ . '/../vendor/autoload.php';

if (!is_file(__DIR__ . '/../config.php')) {
    Response::error('Unable to load the configuration file.');
}

$settings = require __DIR__ . '/../config.php';
$app = new Purge($settings);
