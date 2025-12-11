<?php
// app/controller/adicionar_carrinho.php
session_start();

require_once 'app/config/conexao.php';
require_once 'app/model/carrinho.php';

// Conectar ao banco
$conn = Conexao::getConexao();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $produto_id = (int)$_POST['id'];
    
    // Verificar se o produto existe e não está vendido
    $stmt = $conn->prepare("SELECT vendido, estoque FROM produtos WHERE idProduto = ?");
    $stmt->execute([$produto_id]);
    $produto = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($produto && $produto['vendido'] == 0 && $produto['estoque'] > 0) {
        // Inicializar carrinho na sessão se não existir
        if (!isset($_SESSION['carrinho'])) {
            $_SESSION['carrinho'] = [];
        }
        
        // Adicionar ao carrinho se não estiver lá
        if (!in_array($produto_id, $_SESSION['carrinho'])) {
            $_SESSION['carrinho'][] = $produto_id;
            $_SESSION['mensagem'] = [
                'tipo' => 'success',
                'texto' => 'Produto adicionado ao carrinho!'
            ];
        } else {
            $_SESSION['mensagem'] = [
                'tipo' => 'warning',
                'texto' => 'Produto já está no carrinho!'
            ];
        }
    } else {
        $_SESSION['mensagem'] = [
            'tipo' => 'error',
            'texto' => 'Produto não disponível para compra!'
        ];
    }
}

// Redirecionar de volta
header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'home'));
exit();
?>