<?php
// app/model/Produto.php

class Produto {
    // Constantes das categorias
    const CATEGORIAS = [
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
    private $dataVenda; // Mantém camelCase internamente
    
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
    public function getVendido() { return $this->vendido; }
    
    // GETTERS para data_venda - dois métodos para compatibilidade
    public function getDataVenda() { 
        return $this->dataVenda; 
    }
    
    public function getData_venda() { 
        return $this->dataVenda; 
    }
    
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
    
    // SETTERS para data_venda - dois métodos para compatibilidade
    public function setDataVenda($dataVenda) { 
        $this->dataVenda = $dataVenda; 
        return $this; 
    }
    
    public function setData_venda($data_venda) { 
        $this->dataVenda = $data_venda; 
        return $this; 
    }
    
    // MÉTODOS ÚTEIS
    public function getPrecoFormatado() {
        return 'R$ ' . number_format($this->preco, 2, ',', '.');
    }
    
    public function getNomeCategoria() {
        return self::CATEGORIAS[$this->idCategoria] ?? 'N/A';
    }
    
    public function getStatusEstoqueHTML() {
        if ($this->estoque > 1) {
            return '<span class="badge bg-info">' . $this->estoque . ' un.</span>';
        } elseif ($this->estoque == 1) {
            return '<span class="badge bg-warning">Único</span>';
        } else {
            return '<span class="badge bg-danger">Esgotado</span>';
        }
    }
    
    public function getPromocaoHTML() {
        return $this->promocao ? '<span class="badge bg-success">Sim</span>' : '<span class="badge bg-secondary">Não</span>';
    }
    
    public function getVendidoHTML() {
        return $this->vendido ? '<span class="badge bg-danger">Vendido</span>' : '<span class="badge bg-success">Disponível</span>';
    }
    
    // Método para data de venda formatada
    public function getDataVendaFormatada($formato = 'd/m/Y') {
        if (!$this->dataVenda) {
            return '-';
        }
        
        try {
            $data = new DateTime($this->dataVenda);
            return $data->format($formato);
        } catch (Exception $e) {
            error_log("Erro ao formatar data de venda: " . $e->getMessage());
            return $this->dataVenda;
        }
    }
    
    // Método para data e hora de venda formatada
    public function getDataHoraVendaFormatada() {
        return $this->getDataVendaFormatada('d/m/Y H:i:s');
    }
    
    // Método para verificar se foi vendido recentemente
    public function vendidoRecentemente($dias = 7) {
        if (!$this->vendido || !$this->dataVenda) {
            return false;
        }
        
        try {
            $dataVenda = new DateTime($this->dataVenda);
            $dataAtual = new DateTime();
            $intervalo = $dataAtual->diff($dataVenda);
            return $intervalo->days <= $dias;
        } catch (Exception $e) {
            return false;
        }
    }
    
    // Método para converter em array (útil para API)
    public function toArray() {
        return [
            'id' => $this->idProduto,
            'nome' => $this->nome,
            'marca' => $this->marca,
            'tamanho' => $this->tamanho,
            'estado' => $this->estado,
            'categoria' => $this->getNomeCategoria(),
            'idCategoria' => $this->idCategoria,
            'preco' => $this->preco,
            'imagem' => $this->imagem,
            'promocao' => $this->promocao,
            'estoque' => $this->estoque,
            'descricao' => $this->descricao,
            'vendido' => $this->vendido,
            'dataVenda' => $this->dataVenda,
            'dataVendaFormatada' => $this->getDataVendaFormatada(),
            'dataHoraVendaFormatada' => $this->getDataHoraVendaFormatada(),
            'vendidoRecentemente' => $this->vendidoRecentemente(),
            'promocaoHTML' => $this->getPromocaoHTML(),
            'vendidoHTML' => $this->getVendidoHTML(),
            'statusEstoqueHTML' => $this->getStatusEstoqueHTML(),
            'precoFormatado' => $this->getPrecoFormatado()
        ];
    }
    
    // Método estático para obter categorias
    public static function getCategoriasArray() {
        return self::CATEGORIAS;
    }
    
    // Método estático para obter categoria por ID
    public static function getCategoriaPorId($idCategoria) {
        return self::CATEGORIAS[$idCategoria] ?? 'N/A';
    }
    
    // Método para obter array para formulário (sem dados sensíveis)
    public function toFormArray() {
        return [
            'idProduto' => $this->idProduto,
            'nome' => $this->nome,
            'marca' => $this->marca,
            'tamanho' => $this->tamanho,
            'estado' => $this->estado,
            'idCategoria' => $this->idCategoria,
            'preco' => $this->preco,
            'imagem' => $this->imagem,
            'promocao' => $this->promocao,
            'estoque' => $this->estoque,
            'descricao' => $this->descricao
        ];
    }
}
?>