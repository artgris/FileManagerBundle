<?php

use Symfony\Component\Dotenv\Dotenv;

$file = __DIR__.'/../vendor/autoload.php';
if (!file_exists($file)) {
    throw new RuntimeException('Install dependencies using Composer to run the test suite.');
}
require $file;

if (file_exists(dirname(__DIR__).'/config/bootstrap.php')) {
    require dirname(__DIR__).'/config/bootstrap.php';
} elseif (method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
}

include __DIR__.'/Fixtures/App/AppKernel.php';
