<?php
session_start();
if (isset($_POST['id'])) {
    $id = (int) $_POST['id'];
    if (isset($_SESSION['carrinho'])) {
        $_SESSION['carrinho'] = array_filter($_SESSION['carrinho'], fn($pid) => $pid != $id);
    }
}
header("Location: produtos");
exit;
?>
