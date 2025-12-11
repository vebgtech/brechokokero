<?php
// app/model/Usuario.php

class Usuario {
    private $idUsuario;
    private $nome;
    private $email;
    private $senha;
    private $telefone;
    private $dataCadastro;
    
    public function __construct(
        $nome = '',
        $email = '',
        $senha = '',
        $telefone = '',
        $dataCadastro = null,
        $idUsuario = null
    ) {
        $this->idUsuario = $idUsuario;
        $this->nome = $nome;
        $this->email = $email;
        $this->senha = $senha;
        $this->telefone = $telefone;
        $this->dataCadastro = $dataCadastro ?: date('Y-m-d H:i:s');
    }
    
    // GETTERS
    public function getIdUsuario() { return $this->idUsuario; }
    public function getNome() { return $this->nome; }
    public function getEmail() { return $this->email; }
    public function getSenha() { return $this->senha; }
    public function getTelefone() { return $this->telefone; }
    public function getDataCadastro() { return $this->dataCadastro; }
    
    // SETTERS
    public function setNome($nome) { $this->nome = $nome; return $this; }
    public function setEmail($email) { $this->email = $email; return $this; }
    public function setSenha($senha) { $this->senha = $senha; return $this; }
    public function setTelefone($telefone) { $this->telefone = $telefone; return $this; }
    
    // MÉTODOS ÚTEIS
    public function criptografarSenha() {
        if (!empty($this->senha) && !password_get_info($this->senha)['algo']) {
            $this->senha = password_hash($this->senha, PASSWORD_DEFAULT);
        }
        return $this;
    }
    
    public function verificarSenha($senha) {
        return password_verify($senha, $this->senha);
    }
    
    public function validar() {
        $erros = [];
        
        if (empty($this->nome)) {
            $erros[] = "Nome é obrigatório";
        }
        
        if (empty($this->email)) {
            $erros[] = "Email é obrigatório";
        } elseif (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            $erros[] = "Email inválido";
        }
        
        if (empty($this->senha)) {
            $erros[] = "Senha é obrigatória";
        } elseif (strlen($this->senha) < 6) {
            $erros[] = "Senha deve ter no mínimo 6 caracteres";
        }
        
        return $erros;
    }
    
    public function toArray() {
        return [
            'idUsuario' => $this->idUsuario,
            'nome' => $this->nome,
            'email' => $this->email,
            'telefone' => $this->telefone,
            'dataCadastro' => $this->dataCadastro
        ];
    }
    	public function isAdmin(): bool { //Considera administrador quando idNivelUsuario === 7.
		return (int)($this->idNivelUsuario ?? 0) === 2;
	}
}
?>