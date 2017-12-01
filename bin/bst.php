<?php

use BST\Cli\Test;
use BST\Cli\Server;
use BST\Cli\Worker;
use Symfony\Component\Console\Application;

if (!class_exists('\Symfony\Component\Console\Application')) {
    $paths = [
        __DIR__ . '/../../../autoload.php', // as dependency
        __DIR__ . '/../vendor/autoload.php' // as root
    ];
    foreach ($paths as $path) {
        if (is_file($path)) {
            require_once $path;
            break;
        }
    }
}


$app = new Application();

$app->add(new Test());
$app->add(new Server());
$app->add(new Worker());

$app->run();