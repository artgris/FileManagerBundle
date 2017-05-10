<?php


use Doctrine\Common\Annotations\AnnotationRegistry;

$file = __DIR__.'/../vendor/autoload.php';
if (!file_exists($file)) {
    throw new RuntimeException('Install dependencies using Composer to run the test suite.');
}
$autoload = require $file;

AnnotationRegistry::registerLoader(function ($class) use ($autoload) {
    $autoload->loadClass($class);

    return class_exists($class, false);
});

include __DIR__.'/Fixtures/App/AppKernel.php';
