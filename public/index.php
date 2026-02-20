<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../core/Router.php';

$basePath = dirname($_SERVER['SCRIPT_NAME']);

$router = new Router('v1', $basePath);
$qrResource = new QrResource();

$router->addRoute('POST', '/qr/text', [$qrResource, 'text']);
$router->addRoute('POST', '/qr/url',  [$qrResource, 'url']);
$router->addRoute('POST', '/qr/wifi', [$qrResource, 'wifi']);
$router->addRoute('POST', '/qr/geo',  [$qrResource, 'geo']);

$router->dispatch();
?>