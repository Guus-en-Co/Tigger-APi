<?php

header('content-type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, AUTHORIZATION, X-Requested-With");

if (PHP_SAPI == 'cli-server') {
    // To help the built-in PHP dev server, check if the request was actually for
    // something which should probably be served as a static file
    $url  = parse_url($_SERVER['REQUEST_URI']);
    $file = __DIR__ . $url['path'];
    if (is_file($file)) {
        return false;
    }
}

require __DIR__ . '/../vendor/autoload.php';

session_start();
define('DIRECT_ACCESS', false);

$dotenv = new Dotenv\Dotenv('../includes');
$dotenv->load();
    $dotenv->required(['MYSQL_HOST', 'MYSQL_USERNAME', 'MYSQL_PASSWORD'])->notEmpty();



// Instantiate the app
$settings = require __DIR__ . '/../src/settings.php';
$app = new \Slim\App($settings);

// Set up database
require __DIR__ . '/../includes/connect.php';


// Set up messenger
require __DIR__ . '/../src/Messenger.php';

// Set up dependencies
require __DIR__ . '/../src/dependencies.php';

// Register middleware
require __DIR__ . '/../src/middleware.php';

// Register routes
require __DIR__ . '/../src/routes.php';

// Run app
$app->run();
