<?php
// app/model/UsuarioDAO.php

require_once 'Usuario.php';

class UsuarioDAO {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    public function cadastrar(Usuario $usuario) {
        // Criptografa a senha
        $usuario->criptografarSenha();
        
        $sql = "INSERT INTO usuarios (nome, email, senha, telefone, dataCadastro) 
                VALUES (:nome, :email, :senha, :telefone, :dataCadastro)";
        
        $stmt = $this->conn->prepare($sql);
        
        return $stmt->execute([
            ':nome' => $usuario->getNome(),
            ':email' => $usuario->getEmail(),
            ':senha' => $usuario->getSenha(),
            ':telefone' => $usuario->getTelefone(),
            ':dataCadastro' => $usuario->getDataCadastro()
        ]);
    }
    
    public function buscarPorEmail($email) {
        $sql = "SELECT * FROM usuarios WHERE email = :email";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':email' => $email]);
        $dados = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($dados) {
            return new Usuario(
                $dados['nome'],
                $dados['email'],
                $dados['senha'],
                $dados['telefone'],
                $dados['dataCadastro'],
                $dados['idUsuario']
            );
        }
        
        return null;
    }
    
    public function emailExiste($email) {
        $sql = "SELECT COUNT(*) FROM usuarios WHERE email = :email";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':email' => $email]);
        return $stmt->fetchColumn() > 0;
    }
    
    public function buscarPorId($id) {
        $sql = "SELECT * FROM usuarios WHERE idUsuario = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id' => $id]);
        $dados = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($dados) {
            return new Usuario(
                $dados['nome'],
                $dados['email'],
                $dados['senha'],
                $dados['telefone'],
                $dados['dataCadastro'],
                $dados['idUsuario']
            );
        }
        
        return null;
    }
    
    public function atualizar(Usuario $usuario) {
        $sql = "UPDATE usuarios SET 
                nome = :nome, 
                email = :email, 
                telefone = :telefone 
                WHERE idUsuario = :id";
        
        $stmt = $this->conn->prepare($sql);
        
        return $stmt->execute([
            ':nome' => $usuario->getNome(),
            ':email' => $usuario->getEmail(),
            ':telefone' => $usuario->getTelefone(),
            ':id' => $usuario->getIdUsuario()
        ]);
    }
}
?>