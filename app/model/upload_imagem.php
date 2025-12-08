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
    
    public function upload($arquivo, $nomeAtual = null) {
        if ($arquivo['error'] != 0) {
            return false;
        }
        
        $file_extension = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));
        if (!in_array($file_extension, ['jpg', 'jpeg', 'png', 'gif']) || $arquivo['size'] > 5000000) {
            return false;
        }
        
        $novo_nome = 'produto_' . time() . '_' . rand(1000, 9999) . '.' . $file_extension;
        $target_file = $this->diretorio . $novo_nome;
        
        if (move_uploaded_file($arquivo['tmp_name'], $target_file)) {
            if ($nomeAtual && $nomeAtual != 'default.jpg') {
                $this->removerImagem($nomeAtual);
            }
            return $novo_nome;
        }
        
        return false;
    }
    
    public function removerImagem($nomeArquivo) {
        if ($nomeArquivo != 'default.jpg') {
            $caminho = $this->diretorio . $nomeArquivo;
            if (file_exists($caminho)) {
                return unlink($caminho);
            }
        }
        return false;
    }
}
?>