<?php
require_once 'produto.php';

class Carrinho {
    private $conn;
    private $usuario_id;
    private $itens = [];
    
    public function __construct($conn, $usuario_id = null) {
        $this->conn = $conn;
        $this->usuario_id = $usuario_id;
        $this->carregarItens();
    }
    
    private function carregarItens() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!empty($_SESSION['carrinho'])) {
            $this->itens = $_SESSION['carrinho'];
        }
    }
    
    public function adicionarItem($produto_id) {
        // Verifica se o produto existe e está disponível
        $produto = $this->buscarProduto($produto_id);
        
        if (!$produto) {
            return ['sucesso' => false, 'mensagem' => 'Produto não encontrado'];
        }
        
        if ($produto->isVendido()) {
            return ['sucesso' => false, 'mensagem' => 'Produto já vendido'];
        }
        
        if ($produto->getEstoque() < 1) {
            return ['sucesso' => false, 'mensagem' => 'Produto fora de estoque'];
        }
        
        // Verifica se já está no carrinho
        if (!in_array($produto_id, $this->itens)) {
            $this->itens[] = $produto_id;
            $this->salvarSessao();
            return ['sucesso' => true, 'mensagem' => 'Produto adicionado ao carrinho!'];
        }
        
        return ['sucesso' => false, 'mensagem' => 'Produto já está no carrinho'];
    }
    
    public function removerItem($produto_id) {
        $key = array_search($produto_id, $this->itens);
        if ($key !== false) {
            unset($this->itens[$key]);
            $this->itens = array_values($this->itens);
            $this->salvarSessao();
            return ['sucesso' => true, 'mensagem' => 'Produto removido do carrinho!'];
        }
        
        return ['sucesso' => false, 'mensagem' => 'Produto não encontrado no carrinho'];
    }
    
    public function limparCarrinho() {
        $this->itens = [];
        $this->salvarSessao();
        return ['sucesso' => true, 'mensagem' => 'Carrinho limpo com sucesso!'];
    }
    
    public function getItens() {
        return $this->itens;
    }
    
    public function getQuantidadeItens() {
        return count($this->itens);
    }
    
    public function estaVazio() {
        return empty($this->itens);
    }
    
    public function getTotal() {
        $total = 0;
        
        foreach ($this->itens as $produto_id) {
            $produto = $this->buscarProduto($produto_id);
            if ($produto && !$produto->isVendido()) {
                $total += $produto->getPreco();
            }
        }
        
        return $total;
    }
    
    public function getDetalhesItens() {
        $detalhes = [];
        
        foreach ($this->itens as $produto_id) {
            $produto = $this->buscarProduto($produto_id);
            if ($produto) {
                $detalhes[] = $produto;
            }
        }
        
        return $detalhes;
    }
    
    private function buscarProduto($produto_id) {
        $stmt = $this->conn->prepare("SELECT * FROM produtos WHERE idProduto = ?");
        $stmt->execute([$produto_id]);
        $dados = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($dados) {
            // CORREÇÃO: O Produto precisa de 14 parâmetros, não 13
            // Adicione dataVenda como penúltimo parâmetro
            return new Produto(
                $dados['nome'],
                $dados['marca'],
                $dados['tamanho'],
                $dados['estado'],
                $dados['idCategoria'],
                $dados['preco'],
                $dados['imagem'],
                $dados['promocao'],
                $dados['estoque'],
                $dados['descricao'],
                $dados['vendido'],
                $dados['dataVenda'] ?? null, // PARÂMETRO QUE FALTAVA
                $dados['idProduto']
            );
        }
        
        return null;
    }
    
    public function validarItens() {
        $validos = [];
        $invalidos = [];
        
        foreach ($this->itens as $produto_id) {
            $produto = $this->buscarProduto($produto_id);
            
            if (!$produto) {
                $invalidos[] = ['id' => $produto_id, 'motivo' => 'Produto não encontrado'];
            } elseif ($produto->isVendido()) {
                $invalidos[] = ['id' => $produto_id, 'motivo' => 'Produto já vendido'];
            } elseif ($produto->getEstoque() < 1) {
                $invalidos[] = ['id' => $produto_id, 'motivo' => 'Produto fora de estoque'];
            } else {
                $validos[] = $produto;
            }
        }
        
        return ['validos' => $validos, 'invalidos' => $invalidos];
    }
    
    private function salvarSessao() {
        $_SESSION['carrinho'] = $this->itens;
    }
}
?>