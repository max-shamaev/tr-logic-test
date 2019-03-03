<?php

require __DIR__ . '/../vendor/autoload.php';

// Slim-based router detection hack
$_SERVER['SCRIPT_NAME'] = __FILE__;
if (!empty($_REQUEST['path']) && preg_match('/\/' . basename(__FILE__) . '/Ss', $_SERVER['REQUEST_URI'])) {
    $_SERVER['REQUEST_URI'] = str_replace(basename(__FILE__), ltrim($_REQUEST['path'], '/'), $_SERVER['REQUEST_URI']);
}

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
$app->get('/ping', 'TRLT\Controller\Ping');

// Logging
$logger = new \Monolog\Logger('Common logger');

$logger->pushHandler(
    new \Monolog\Handler\StreamHandler($app->getContainer()->directories['log'] . '/common.log')
);

\Monolog\ErrorHandler::register($logger);
