<?php
// app/model/UploadImagem.php

class UploadImagem {
    private $diretorio;
    
    public function __construct($diretorio = '../../../public/img/') {
        $this->diretorio = $diretorio;
        
        if (!is_dir($this->diretorio)) { 
            mkdir($this->diretorio, 0775, true); 
        }
    }
    
    /**
     * Upload de imagem a partir de arquivo ou JSON
     */
    public function upload($dados, $nomeAtual = null) {
        // Verifica se é um arquivo tradicional
        if (is_array($dados) && isset($dados['tmp_name'])) {
            return $this->uploadArquivo($dados, $nomeAtual);
        }
        
        // Verifica se é um JSON com imagem base64
        if (is_string($dados) && $this->isJsonComImagem($dados)) {
            return $this->uploadJson($dados, $nomeAtual);
        }
        
        return false;
    }
    
    /**
     * Upload tradicional de arquivo
     */
    private function uploadArquivo($arquivo, $nomeAtual = null) {
        if ($arquivo['error'] != 0) {
            return false;
        }
        
        $file_extension = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));
        if (!$this->validarImagem($file_extension, $arquivo['size'])) {
            return false;
        }
        
        $novo_nome = $this->gerarNomeArquivo($file_extension);
        $target_file = $this->diretorio . $novo_nome;
        
        if (move_uploaded_file($arquivo['tmp_name'], $target_file)) {
            $this->removerImagemAntiga($nomeAtual);
            return $novo_nome;
        }
        
        return false;
    }
    
    /**
     * Upload a partir de JSON com imagem base64
     */
    private function uploadJson($jsonString, $nomeAtual = null) {
        $dados = json_decode($jsonString, true);
        
        if (!$dados || !isset($dados['imagem']) || !isset($dados['extensao'])) {
            return false;
        }
        
        $imagemBase64 = $dados['imagem'];
        $file_extension = strtolower($dados['extensao']);
        
        // Valida a extensão
        if (!$this->validarImagem($file_extension, strlen($imagemBase64))) {
            return false;
        }
        
        // Remove o cabeçalho data:image se existir
        if (strpos($imagemBase64, 'base64,') !== false) {
            $parts = explode('base64,', $imagemBase64);
            $imagemBase64 = $parts[1];
        }
        
        // Decodifica a imagem base64
        $imagemDecodificada = base64_decode($imagemBase64);
        
        if ($imagemDecodificada === false) {
            return false;
        }
        
        // Gera nome do arquivo e salva
        $novo_nome = $this->gerarNomeArquivo($file_extension);
        $target_file = $this->diretorio . $novo_nome;
        
        if (file_put_contents($target_file, $imagemDecodificada)) {
            $this->removerImagemAntiga($nomeAtual);
            return $novo_nome;
        }
        
        return false;
    }
    
    /**
     * Verifica se a string é um JSON válido com dados de imagem
     */
    private function isJsonComImagem($string) {
        $dados = json_decode($string, true);
        
        return (
            json_last_error() === JSON_ERROR_NONE &&
            is_array($dados) &&
            isset($dados['imagem']) &&
            isset($dados['extensao'])
        );
    }
    
    /**
     * Valida extensão e tamanho da imagem
     */
    private function validarImagem($extensao, $tamanho) {
        $extensoesPermitidas = ['jpg', 'jpeg', 'png', 'gif'];
        $tamanhoMaximo = 5000000; // 5MB
        
        return (
            in_array($extensao, $extensoesPermitidas) &&
            $tamanho <= $tamanhoMaximo
        );
    }
    
    /**
     * Gera um nome único para o arquivo
     */
    private function gerarNomeArquivo($extensao) {
        return 'produto_' . time() . '_' . rand(1000, 9999) . '.' . $extensao;
    }
    
    /**
     * Remove imagem antiga se necessário
     */
    private function removerImagemAntiga($nomeArquivo) {
        if ($nomeArquivo && $nomeArquivo != 'default.jpg') {
            $this->removerImagem($nomeArquivo);
        }
    }
    
    /**
     * Remove imagem do diretório
     */
    public function removerImagem($nomeArquivo) {
        if ($nomeArquivo != 'default.jpg') {
            $caminho = $this->diretorio . $nomeArquivo;
            if (file_exists($caminho)) {
                return unlink($caminho);
            }
        }
        return false;
    }
    
    /**
     * Método para criar um JSON a partir de uma imagem (útil para API)
     */
    public function imagemParaJson($caminhoImagem) {
        if (!file_exists($caminhoImagem)) {
            return false;
        }
        
        $extensao = strtolower(pathinfo($caminhoImagem, PATHINFO_EXTENSION));
        $conteudo = file_get_contents($caminhoImagem);
        
        if ($conteudo === false) {
            return false;
        }
        
        $imagemBase64 = base64_encode($conteudo);
        
        return json_encode([
            'imagem' => $imagemBase64,
            'extensao' => $extensao,
            'mime_type' => mime_content_type($caminhoImagem),
            'tamanho' => filesize($caminhoImagem)
        ]);
    }
}
?>