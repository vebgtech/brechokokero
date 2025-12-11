<?php
// app/api.php - VERSÃO COM VERIFICAÇÃO DE EXECUÇÃO

// VERIFICAÇÃO INICIAL - Se não estiver em ambiente PHP, exibe erro
if (!defined('PHP_VERSION')) {
    header('Content-Type: text/plain');
    die("ERRO: Este arquivo deve ser executado em servidor PHP\n" .
        "Você está acessando diretamente via HTTP?");
}

// 1. SESSÃO (deve ser a primeira coisa)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. CONFIGURAÇÃO DE ERROS (só em desenvolvimento)
if (isset($_GET['debug']) || php_sapi_name() === 'cli-server') {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
}

// 3. HEADERS para JSON
header('Content-Type: application/json; charset=utf-8');

// 4. FUNÇÃO DE RESPOSTA
function json_response($success, $message, $data = [], $code = 200)
{
    http_response_code($code);
    $response = [
        'success' => $success,
        'message' => $message,
        'data' => $data,
        'timestamp' => date('Y-m-d H:i:s')
    ];

    // Se for debug, adiciona informações extras
    if (isset($_GET['debug'])) {
        $response['debug'] = [
            'php_version' => PHP_VERSION,
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'N/A',
            'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'N/A',
            'request_uri' => $_SERVER['REQUEST_URI'] ?? 'N/A'
        ];
    }

    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit();
}

// 5. VERIFICAÇÃO DE AMBIENTE
try {
    // Testa se estamos em ambiente PHP
    if (!function_exists('json_encode')) {
        throw new Exception('Extensão JSON não disponível');
    }

    // 6. VERIFICAR LOGIN ADMIN
    if (!isset($_SESSION['idNivelUsuario']) || $_SESSION['idNivelUsuario'] < 2) {
        json_response(false, 'Acesso não autorizado. Área restrita a administradores.', [], 403);
    }

    // 7. VERIFICAR AÇÃO
    $action = $_GET['action'] ?? '';
    if (empty($action)) {
        json_response(false, 'Ação não especificada. Use ?action=listar', [], 400);
    }

    // 8. TESTE RÁPIDO PARA VERIFICAR SE A API ESTÁ FUNCIONANDO
    if ($action === 'ping') {
        json_response(true, 'API funcionando', [
            'php_version' => PHP_VERSION,
            'server' => $_SERVER['SERVER_SOFTWARE'] ?? 'N/A',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }

    // 9. BANCO DE DADOS
    $configPath = __DIR__ . '/config/conexao.php';
    if (!file_exists($configPath)) {
        throw new Exception("Arquivo de configuração não encontrado: " . $configPath);
    }

    require_once $configPath;

    if (!class_exists('Conexao')) {
        throw new Exception("Classe Conexao não encontrada");
    }

    $conn = Conexao::getConexao();

    if (!$conn) {
        json_response(false, 'Erro na conexão com o banco de dados', [], 500);
    }

    // 10. CARREGAR CLASSES
    $baseDir = __DIR__ . '/';
    $classes = [
        'model/produto.php',
        'model/produto_dao.php',
        'controller/admincontroller.php'
    ];

    foreach ($classes as $classFile) {
        $fullPath = $baseDir . $classFile;
        if (!file_exists($fullPath)) {
            error_log("Arquivo não encontrado: $fullPath");
            throw new Exception("Arquivo necessário não encontrado: " . basename($classFile));
        }
        require_once $fullPath;
    }

    // 11. CRIAR CONTROLLER
    $controller = new AdminController($conn);

    // 12. PROCESSAR AÇÕES
    switch ($action) {
        case 'listar':
            $filtros = $_GET;
            unset($filtros['action']);
            $controller->listarProdutos($filtros);
            break;

        case 'cadastrar':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                json_response(false, 'Método não permitido. Use POST.', [], 405);
            }

            // Debug
            error_log("API: Recebendo cadastro - POST: " . print_r($_POST, true));
            error_log("API: Recebendo cadastro - FILES: " . print_r($_FILES, true));

            $controller->cadastrarProduto($_POST);
            break;

        case 'atualizar':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                json_response(false, 'Método não permitido. Use POST.', [], 405);
            }

            if (empty($_POST['id'])) {
                json_response(false, 'ID do produto não informado', [], 400);
            }

            error_log("API: Recebendo atualização - POST: " . print_r($_POST, true));
            error_log("API: Recebendo atualização - FILES: " . print_r($_FILES, true));

            // ↓↓↓ CORREÇÃO AQUI ↓↓↓
            $arquivoImagem = isset($_FILES['imagem']) ? $_FILES['imagem'] : null;
            $controller->atualizarProduto($_POST, $arquivoImagem); // <-- Passe a imagem também!
            break;
        case 'test':
            json_response(true, 'Teste OK', [
                'session' => [
                    'idNivelUsuario' => $_SESSION['idNivelUsuario'] ?? 'N/A',
                    'nome' => $_SESSION['nome'] ?? 'N/A'
                ],
                'php_version' => PHP_VERSION,
                'server' => $_SERVER['SERVER_SOFTWARE'] ?? 'N/A'
            ]);
            break;

            // api.php - NO FINAL DO SWITCH (após o case 'atualizar')
case 'excluir':
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        json_response(false, 'Método não permitido. Use POST.', [], 405);
    }
    
    if (empty($_POST['id'])) {
        json_response(false, 'ID do produto não informado', [], 400);
    }
    
    $controller->excluirProduto($_POST);
    break;

case 'marcar_vendido':
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        json_response(false, 'Método não permitido. Use POST.', [], 405);
    }
    
    if (empty($_POST['id'])) {
        json_response(false, 'ID do produto não informado', [], 400);
    }
    
    $controller->marcarComoVendido($_POST);
    break;

        default:
            json_response(false, 'Ação não implementada: ' . $action, [], 404);
            break;
    }
} catch (Exception $e) {
    error_log("ERRO API: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    json_response(false, 'Erro interno: ' . $e->getMessage(), [], 500);
}
