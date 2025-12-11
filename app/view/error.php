<?php
// ============================================================================
// ZaitTiny Framework - Página Genérica de Erros
// ----------------------------------------------------------------------------
// Aceita parâmetros GET:
//   ?code=403
//   ?msg=Mensagem personalizada
//
// Uso:
//   require 'error.php';
//   exit;
//
// Ou:
//   header("Location: /error.php?code=404&msg=Página não encontrada");
// ============================================================================

$code = isset($_GET['code']) ? (int)$_GET['code'] : 404;
$msg  = $_GET['msg'] ?? '';

$validCodes = [400,401,403,404,408,409,410,422,429,500,501,503];
if (!in_array($code, $validCodes)) {
	$code = 500; // fallback seguro
}

http_response_code($code);

// Mensagens padrão caso o usuário não informe uma msg
$defaultMessages = [
		400 => 'Requisição inválida',
		401 => 'Não autorizado',
		403 => 'Acesso negado',
		404 => 'Página não encontrada',
		408 => 'Tempo limite excedido',
		409 => 'Conflito de dados',
		410 => 'Recurso não está mais disponível',
		422 => 'Dados inválidos',
		429 => 'Solicitações em excesso',
		500 => 'Erro interno no servidor',
		501 => 'Funcionalidade não implementada',
		503 => 'Serviço temporariamente indisponível'
];

if (trim($msg) === '') {
	$msg = $defaultMessages[$code] ?? 'Erro desconhecido';
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Erro <?= htmlspecialchars($code) ?></title>
	<style>
		body {
			margin: 0;
			padding: 0;
			font-family: Arial, Helvetica, sans-serif;
			background: #000000ff;
			display: flex;
			justify-content: center;
			align-items: center;
			height: 100vh;
			text-align: center;
			color: #333;
		}
		.container {
			max-width: 480px;
			padding: 25px;
			background: #fff;
			box-shadow: 0 0 20px rgba(0,0,0,0.1);
			border-radius: 10px;
		}
		h1 {
			font-size: 70px;
			margin: 0;
			font-weight: bold;
			color: #014d00;
		}
		h2 {
			font-size: 26px;
			margin-top: 10px;
		}
		p {
			color: #666;
			font-size: 16px;
			margin-top: 10px;
		}
		a.btn {
			display: inline-block;
			margin-top: 20px;
			padding: 10px 30px;
			background: #014d00;
			color: white;
			text-decoration: none;
			border-radius: 5px;
			font-size: 16px;
			transition: 0.3s;
		}
		a.btn:hover {
			background: #0056b3;
		}
	</style>
</head>
<body>

	<div class="container">
		<h1><?= htmlspecialchars($code) ?></h1>
		<h2><?= htmlspecialchars($msg) ?></h2>
		<p>Se o problema persistir, entre em contato com o administrador do sistema.</p>
		<a href="home" class="btn">Voltar ao início</a>
	</div>

</body>
</html>
