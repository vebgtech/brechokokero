<?php
// app/model/Produto.php

class Produto {
    private $idProduto;
    private $nome;
    private $marca;
    private $tamanho;
    private $estado;
    private $idCategoria;
    private $preco;
    private $imagem;
    private $promocao;
    private $estoque;
    private $descricao;
    private $vendido;
    private $dataVenda;
    
    public function __construct(
        $nome = '',
        $marca = 'Sem Marca',
        $tamanho = '',
        $estado = '',
        $idCategoria = 0,
        $preco = 0.0,
        $imagem = 'default.jpg',
        $promocao = false,
        $estoque = 1,
        $descricao = '',
        $vendido = false,
        $dataVenda = null,
        $idProduto = null
    ) {
        $this->idProduto = $idProduto;
        $this->nome = $nome;
        $this->marca = $marca;
        $this->tamanho = $tamanho;
        $this->estado = $estado;
        $this->idCategoria = $idCategoria;
        $this->preco = $preco;
        $this->imagem = $imagem;
        $this->promocao = $promocao;
        $this->estoque = $estoque;
        $this->descricao = $descricao;
        $this->vendido = $vendido;
        $this->dataVenda = $dataVenda;
    }
    
    // GETTERS
    public function getIdProduto() { return $this->idProduto; }
    public function getNome() { return $this->nome; }
    public function getMarca() { return $this->marca; }
    public function getTamanho() { return $this->tamanho; }
    public function getEstado() { return $this->estado; }
    public function getIdCategoria() { return $this->idCategoria; }
    public function getPreco() { return $this->preco; }
    public function getImagem() { return $this->imagem; }
    public function isPromocao() { return $this->promocao; }
    public function getEstoque() { return $this->estoque; }
    public function getDescricao() { return $this->descricao; }
    public function isVendido() { return $this->vendido; }
    public function getDataVenda() { return $this->dataVenda; }
    
    // SETTERS
    public function setIdProduto($idProduto) { $this->idProduto = $idProduto; return $this; }
    public function setNome($nome) { $this->nome = $nome; return $this; }
    public function setMarca($marca) { $this->marca = $marca; return $this; }
    public function setTamanho($tamanho) { $this->tamanho = $tamanho; return $this; }
    public function setEstado($estado) { $this->estado = $estado; return $this; }
    public function setIdCategoria($idCategoria) { $this->idCategoria = $idCategoria; return $this; }
    public function setPreco($preco) { $this->preco = $preco; return $this; }
    public function setImagem($imagem) { $this->imagem = $imagem; return $this; }
    public function setPromocao($promocao) { $this->promocao = $promocao; return $this; }
    public function setEstoque($estoque) { $this->estoque = $estoque; return $this; }
    public function setDescricao($descricao) { $this->descricao = $descricao; return $this; }
    public function setVendido($vendido) { $this->vendido = $vendido; return $this; }
    public function setDataVenda($dataVenda) { $this->dataVenda = $dataVenda; return $this; }
    
    // MÉTODOS ÚTEIS
    public function getPrecoFormatado() {
        return 'R$ ' . number_format($this->preco, 2, ',', '.');
    }
    
    public function getNomeCategoria() {
        $categorias = [
            1 => 'Bermudas e Shorts',
            2 => 'Blazers',
            3 => 'Blusas e Camisas',
            4 => 'Calças',
            5 => 'Casacos e Jaquetas',
            6 => 'Conjuntos',
            7 => 'Saias',
            8 => 'Sapatos',
            9 => 'Social',
            10 => 'Vestidos'
        ];
        return $categorias[$this->idCategoria] ?? 'N/A';
    }
    
    public function getStatusEstoqueHTML() {
        if ($this->estoque == 1) {
            return '<span class="badge bg-info">Produto Único</span>';
        } elseif ($this->estoque > 1) {
            return '<span class="badge bg-success">Sim (' . $this->estoque . ')</span>';
        } else {
            return '<span class="badge bg-danger">Não</span>';
        }
    }
    
    public function getPromocaoHTML() {
        return $this->promocao ? '<span class="badge badge-promocao">Sim</span>' : '<span class="badge bg-secondary">Não</span>';
    }
    
    public function getVendidoHTML() {
        return $this->vendido ? '<span class="badge badge-vendido">Sim</span>' : '<span class="badge bg-secondary">Não</span>';
    }
}
?>