<?php
use app\core\Router;

/*----------------------------------------------------------------------------*/
require_once __DIR__ . '/zait_autoload.php'; 	// Autoload do ZaitTiny (classes dentro de /app)
require_once __DIR__ . '/vendor/autoload.php'; 	// Autoload do Composer (bibliotecas externas: PHPMailer, Twilio etc.)
/*----------------------------------------------------------------------------*/

if (session_status() === PHP_SESSION_NONE) {
	session_start();
}
/*----------------------------------------------------------------------------*/
$configFile = __DIR__ . '/app/etc/config.json';
if (!file_exists($configFile)) {
	die("Erro fatal: arquivo de configuração 'app/etc/config.json' não encontrado.");
}
$config = json_decode(file_get_contents($configFile), true);
if (!is_array($config)) {
	die("Erro fatal: não foi possível interpretar 'config.json'. Verifique o JSON.");
}
$_SESSION['config'] = $config;


if ($_SESSION['config']['app']['env'] === 'dev') {
	ini_set('display_errors', '1');
	ini_set('display_startup_errors', '1');
	error_reporting(E_ALL);
} else {
	ini_set('display_errors', '0');
	error_reporting(E_ALL & ~E_NOTICE);
}

/*----------------------------------------------------------------------------*/
$routesFile = __DIR__ . '/app/etc/routes.json';
if (!file_exists($routesFile)) {
	die("Erro fatal: arquivo de rotas 'app/etc/routes.json' não encontrado.");
}
$routes = json_decode(file_get_contents($routesFile), true);
if (!is_array($routes)) {
	die("Erro fatal: não foi possível interpretar 'routes.json'. Verifique o JSON.");
}
$_SESSION['route']  = $routes;
/*----------------------------------------------------------------------------*/
$uri = $_GET['uri'] ?? 'home';
$router = new Router();
$router->get($uri);
?>