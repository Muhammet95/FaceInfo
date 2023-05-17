<?php

use App\Controllers\RouteController;
use Slim\Factory\AppFactory;

require __DIR__ . '/vendor/autoload.php';

$app = AppFactory::create();

$app->post('/', RouteController::class . ':post');
$app->get('/', RouteController::class . ':get');


$app->run();