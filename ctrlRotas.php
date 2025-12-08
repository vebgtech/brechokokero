<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
error_reporting(E_ALL);
ini_set('display_errors', 1);

$uri = $_GET["uri"] ?? '';
$uri = trim($uri, "/");

$uri = explode("?", $uri)[0];

if (!preg_match('/^[a-zA-Z0-9\-\/]*$/', $uri)) {
    http_response_code(400);
    require "error.php";
    exit();
}

if (str_contains($uri, '..')) {
    http_response_code(403);
    require "error.php";
    exit();
}

$routes = [
    ''          => [ 'path'=> 'public/index.php',        'login'=>false, 'nivelAcesso'=>0 ],
    'index'     => [ 'path'=> 'public/index.php',        'login'=>false, 'nivelAcesso'=>0 ],
    'main'      => [ 'path'=> 'app/view/minha_conta.php','login'=>true,  'nivelAcesso'=>1 ],
    'admin'     => [ 'path'=> 'app/view/admin/admin.php','login'=>true,  'nivelAcesso'=>2 ],
    'produtos'  => [ 'path'=> 'app/view/produtos.php',   'login'=>false, 'nivelAcesso'=>0 ],
    'faq'       => [ 'path'=> 'app/view/faq.php',        'login'=>false, 'nivelAcesso'=>0 ],
    'login'     => [ 'path'=> 'public/log.php',          'login'=>false, 'nivelAcesso'=>0 ],
    'cadastro'  => [ 'path'=> 'public/cadastre.php',     'login'=>false, 'nivelAcesso'=>0 ],
    'produto'   => [ 'path'=> 'app/view/produto_detalhes.php','login'=>false,'nivelAcesso'=>0 ],
    'checkout'  => [ 'path'=> 'app/view/checkout.php',   'login'=>true,  'nivelAcesso'=>1 ],
    'error'     => [ 'path'=> 'app/view/error.php',      'login'=>false, 'nivelAcesso'=>0 ],
];

if (str_starts_with($uri, "public/")) {
    safeServeFile($uri);
    exit();
}

if (array_key_exists($uri, $routes)) {

    $route = $routes[$uri];

    if ($route['login']) {

        if (!isset($_SESSION['idUsuario'])) {
            header("Location: /login");
            exit();
        }

        $nivel = $_SESSION['idNivelUsuario'] ?? 0;

        if ($nivel < $route['nivelAcesso']) {
            header("Location: /error?code=403&msg=Acesso%20negado");
            exit();
        }
    }

    safeServeFile($route['path']);
    exit();
}

http_response_code(404);
require "error.php";
exit();

function safeServeFile(string $file): void {

    if (str_contains($file, "..")) {
        http_response_code(403);
        require "error.php";
        exit();
    }

    $base = __DIR__ . "/";
    $path = realpath($base . $file);

    if (!$path || !str_starts_with($path, $base)) {
        http_response_code(404);
        require "error.php";
        exit();
    }

    $ext = pathinfo($path, PATHINFO_EXTENSION);

    $interpreted = ['php','html','css','js','json','xml','txt'];

    if (in_array($ext, $interpreted)) {
        require $path;
        exit();
    }

    $mime = mime_content_type($path);
    header("Content-Type: $mime");
    readfile($path);
    exit();
}

?>
