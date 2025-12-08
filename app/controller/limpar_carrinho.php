<?php
session_start();

// Limpa o carrinho
if (isset($_SESSION['carrinho'])) {
    $_SESSION['carrinho'] = array();
}

// Retorna resposta de sucesso
http_response_code(200);
echo "Carrinho limpo com sucesso";
?>