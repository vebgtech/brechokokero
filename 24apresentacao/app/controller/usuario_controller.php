<?php
// app/controller/UsuarioController.php

require_once 'app/model/usuario.php';
require_once 'app/model/usuario_dao.php';

class UsuarioController {
    private $usuarioDAO;
    
    public function __construct($conn) {
        $this->usuarioDAO = new UsuarioDAO($conn);
    }
    
    public function cadastrar() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cadastro'])) {
            // Validar CSRF token (se estiver usando)
            // if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            //     $_SESSION['erro'] = 'Token de segurança inválido';
            //     header('Location: cadastro.php');
            //     exit();
            // }
            
            // Criar objeto Usuario
            $usuario = new Usuario(
                trim($_POST['nome']),
                trim($_POST['email']),
                $_POST['senha'],
                trim($_POST['telefone'] ?? '')
            );
            
            // Validar
            $erros = $usuario->validar();
            
            // Verificar se email já existe
            if (empty($erros) && $this->usuarioDAO->emailExiste($usuario->getEmail())) {
                $erros[] = 'Este email já está cadastrado';
            }
            
            if (empty($erros)) {
                // Tentar cadastrar
                if ($this->usuarioDAO->cadastrar($usuario)) {
                    $_SESSION['sucesso'] = 'Cadastro realizado com sucesso! Faça login para continuar.';
                    header('Location: log.php');
                    exit();
                } else {
                    $_SESSION['erro'] = 'Erro ao cadastrar. Tente novamente.';
                }
            } else {
                $_SESSION['erros_cadastro'] = $erros;
                $_SESSION['dados_form'] = [
                    'nome' => $usuario->getNome(),
                    'email' => $usuario->getEmail(),
                    'telefone' => $usuario->getTelefone()
                ];
            }
            
            header('Location: cadastro.php');
            exit();
        }
    }
    
    public function login() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
            $email = trim($_POST['email']);
            $senha = $_POST['senha'];
            
            if (empty($email) || empty($senha)) {
                $_SESSION['erro'] = 'Preencha todos os campos';
                header('Location: log.php');
                exit();
            }
            
            // Buscar usuário
            $usuario = $this->usuarioDAO->buscarPorEmail($email);
            
            if ($usuario && $usuario->verificarSenha($senha)) {
                // Login bem-sucedido
                $_SESSION['usuario_id'] = $usuario->getIdUsuario();
                $_SESSION['usuario_nome'] = $usuario->getNome();
                $_SESSION['usuario_email'] = $usuario->getEmail();
                
                // Redirecionar para página anterior ou index
                $redirect = $_SESSION['redirect_after_login'] ?? 'index.php';
                unset($_SESSION['redirect_after_login']);
                
                header('Location: ' . $redirect);
                exit();
            } else {
                $_SESSION['erro'] = 'Email ou senha incorretos';
                header('Location: log.php');
                exit();
            }
        }
    }
    
    public function logout() {
        session_destroy();
        header('Location: index.php');
        exit();
    }
    
    public function getUsuarioPorId($id) {
        return $this->usuarioDAO->buscarPorId($id);
    }
}
?>