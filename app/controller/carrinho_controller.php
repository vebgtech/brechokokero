<?php
// app/controller/CarrinhoController.php

// O caminho está correto agora
require_once __DIR__ . '/../model/carrinho.php';

class CarrinhoController {
    private $carrinho;
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $usuario_id = $_SESSION['usuario_id'] ?? null;
        $this->carrinho = new Carrinho($conn, $usuario_id);
    }
    
    public function adicionar() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
            $produto_id = (int)$_POST['id'];
            
            if ($produto_id) {
                $resultado = $this->carrinho->adicionarItem($produto_id);
                
                $_SESSION['mensagem'] = [
                    'tipo' => $resultado['sucesso'] ? 'success' : 'warning',
                    'texto' => $resultado['mensagem']
                ];
            }
            
            $this->redirecionarOrigem();
        }
    }
    
    public function remover() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
            $produto_id = (int)$_POST['id'];
            
            if ($produto_id) {
                $resultado = $this->carrinho->removerItem($produto_id);
                
                if ($resultado['sucesso']) {
                    $_SESSION['mensagem'] = [
                        'tipo' => 'success',
                        'texto' => $resultado['mensagem']
                    ];
                }
            }
            
            $this->redirecionarOrigem();
        }
    }
    
    public function limpar() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $resultado = $this->carrinho->limparCarrinho();
            
            $_SESSION['mensagem'] = [
                'tipo' => 'success',
                'texto' => $resultado['mensagem']
            ];
            
            $this->redirecionarOrigem();
        }
    }
    
    public function getCarrinho() {
        return $this->carrinho;
    }
    
    public function processarCheckout() {
        if (!isset($_SESSION['usuario_id'])) {
            $_SESSION['mensagem'] = [
                'tipo' => 'error',
                'texto' => 'Você precisa estar logado para finalizar a compra'
            ];
            header('Location: ../view/log.php');
            exit();
        }
        
        $validacao = $this->carrinho->validarItens();
        
        if (!empty($validacao['invalidos'])) {
            $_SESSION['mensagem'] = [
                'tipo' => 'warning',
                'texto' => 'Alguns itens do seu carrinho não estão mais disponíveis'
            ];
            
            foreach ($validacao['invalidos'] as $invalido) {
                $this->carrinho->removerItem($invalido['id']);
            }
        }
        
        header('Location: ../view/checkout.php');
        exit();
    }
    
    private function redirecionarOrigem() {
        $origem = $_SERVER['HTTP_REFERER'] ?? 'index.php';
        header('Location: ' . $origem);
        exit();
    }
    
    public function adicionarAJAX() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
            $produto_id = (int)$_POST['id'];
            
            if ($produto_id) {
                $resultado = $this->carrinho->adicionarItem($produto_id);
                
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => $resultado['sucesso'],
                    'message' => $resultado['mensagem'],
                    'quantidade' => $this->carrinho->getQuantidadeItens()
                ]);
                exit();
            }
        }
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Requisição inválida'
        ]);
        exit();
    }
}
?>