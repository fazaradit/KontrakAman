<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Router;
use App\Core\Request;
use Dotenv\Dotenv;

// Load environment variables
if (class_exists(Dotenv::class)) {
    $dotenv = Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->safeLoad();
}

$router = new Router();
$request = new Request();

// Register routes
$router->get('/', [\App\Controllers\HomeController::class, 'index']);

// Dispatch
$router->dispatch($request->getMethod(), $request->getUri());
