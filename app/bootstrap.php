<?php

require __DIR__ . '/../vendor/autoload.php';

// Instance core & set settings
$app = new \Slim\App(
    [
        'directories' => [
            'log'    => __DIR__ . '/../var/log',
            'images' => __DIR__ . '/../var/images',
        ],
    ]
);
$app->post('/images', 'TRLT\Controller\Images:uploadImage');

// Logging
$logger = new \Monolog\Logger('Common logger');

$logger->pushHandler(
    new \Monolog\Handler\StreamHandler($app->getContainer()->directories['log'] . '/common.log')
);

\Monolog\ErrorHandler::register($logger);
