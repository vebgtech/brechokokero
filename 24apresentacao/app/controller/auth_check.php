<?php
session_start();

// Verificar se o usuário NÃO está logado
if (!isset($_SESSION['usuario']) || $_SESSION['usuario'] !== true) {
    // Salvar a página que tentou acessar
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    
    // Redirecionar para login
    header("Location: login?erro=acesso_negado");
    exit();
}

// Atualizar tempo do último acesso
$_SESSION['ultimo_acesso'] = time();
?>