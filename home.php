<?php
require_once 'app/config/conexao.php';
require_once 'app/model/carrinho.php';
require_once 'app/view/carrinho_view.php';
require_once 'app/controller/carrinho_controller.php';

$conn = Conexao::getConexao();

$carrinhoController = new CarrinhoController($conn);
$carrinho = $carrinhoController->getCarrinho();
$carrinhoView = new CarrinhoView($carrinho);

$isAdmin = ($_SESSION['idNivelUsuario'] ?? 0) >= 2;

// Busca e filtros (mantidos para outras páginas)
$termo_busca = $_GET['busca'] ?? '';
$filtro_estado = $_GET['filtro_estado'] ?? '';
$filtro_tamanho = $_GET['filtro_tamanho'] ?? '';
$filtro_marca = $_GET['filtro_marca'] ?? '';

// Opções para filtros (mantidas para outras páginas)
$estados = $conn->query("SELECT DISTINCT estado FROM produtos WHERE estado IS NOT NULL AND vendido=0 ORDER BY estado")->fetchAll(PDO::FETCH_ASSOC);
$tamanho = $conn->query("SELECT DISTINCT tamanho FROM produtos WHERE tamanho IS NOT NULL AND vendido=0 ORDER BY tamanho")->fetchAll(PDO::FETCH_ASSOC);
$marcas = $conn->query("SELECT DISTINCT marca FROM produtos WHERE marca IS NOT NULL AND vendido=0 ORDER BY marca")->fetchAll(PDO::FETCH_ASSOC);

// Array com as novas categorias
$categorias = [
    1 => "Bermudas e Shorts",
    2 => "Blazers",
    3 => "Blusas e Camisas",
    4 => "Calças",
    5 => "Casacos e Jaquetas",
    6 => "Conjuntos",
    7 => "Saias",
    8 => "Sapatos",
    9 => "Social",
    10 => "Vestidos"
];

// NÃO fazemos mais consultas de produtos aqui! Serão carregados via AJAX
echo $carrinhoView->renderizarOffcanvas();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="icon" type="image/png" href="public/img/logo.png">
  <title>Brechó Koꓘero</title> 
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
  <link rel="stylesheet" href="public/css/estilo.css">
  
  <!-- Script para passar dados PHP para JavaScript -->
  <script>
    // Dados da sessão para JavaScript
    const sessionData = {
      carrinhoCount: <?php echo isset($_SESSION['carrinho']) && is_array($_SESSION['carrinho']) ? count($_SESSION['carrinho']) : 0; ?>,
      isAdmin: <?php echo $isAdmin ? 'true' : 'false'; ?>,
      mensagens: <?php 
        if (isset($_SESSION['mensagem'])) {
          echo json_encode([$_SESSION['mensagem']]);
          unset($_SESSION['mensagem']);
        } else {
          echo '[]';
        }
      ?>
    };
  </script>
</head>
<body>

<!-- ===========================
     NAVBAR PRINCIPAL
=========================== -->
<nav class="navbar navbar-expand-lg">
    <div class="container-fluid">
        <a class="navbar-brand" href="home">
            <img src="public/img/logo.png" alt="Logo" style="height: 80px; width:auto;">
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
            <span class="navbar-toggler-icon" style="color:#fff;"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarContent">
            <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link active" href="home">Início</a></li>
                <li class="nav-item"><a class="nav-link" href="produtos">Produtos</a></li>
                <li class="nav-item"><a class="nav-link" href="faq">FAQ</a></li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="dropdownMenu" role="button" data-bs-toggle="dropdown">
                        Categorias
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="dropdownMenu">
                        <li><a class="dropdown-item" href="produtos#novidade">Novidade</a></li>
                        <li><a class="dropdown-item" href="produtos#todos">Todos</a></li>
                        <li><a class="dropdown-item" href="produtos#promocoes">Promoções</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="produtos#bermudas-shorts">Bermudas e Shorts</a></li>
                        <li><a class="dropdown-item" href="produtos#blazers">Blazers</a></li>
                        <li><a class="dropdown-item" href="produtos#blusas-camisas">Blusas e Camisas</a></li>
                        <li><a class="dropdown-item" href="produtos#calcas">Calças</a></li>
                        <li><a class="dropdown-item" href="produtos#casacos-jaquetas">Casacos e Jaquetas</a></li>
                        <li><a class="dropdown-item" href="produtos#conjuntos">Conjuntos</a></li>
                        <li><a class="dropdown-item" href="produtos#saias">Saias</a></li>
                        <li><a class="dropdown-item" href="produtos#sapatos">Sapatos</a></li>
                        <li><a class="dropdown-item" href="produtos#social">Social</a></li>
                        <li><a class="dropdown-item" href="produtos#vestidos">Vestidos</a></li>
                    </ul>
                </li>
                
                <!-- BOTÃO ADMIN - APENAS PARA USUÁRIOS COM NÍVEL 2 -->
                <?php if ($isAdmin): ?>
                <li class="nav-item">
                    <a class="nav-link" href="admin">
                        <i class="bi bi-shield-lock me-1"></i> Admin
                    </a>
                </li>
                <?php endif; ?>
            </ul>

            <form class="d-flex me-3" role="search" method="GET" action="produtos">
                <input class="form-control me-2" type="search" name="busca" placeholder="Buscar produtos..."
                    value="<?php echo htmlspecialchars($termo_busca); ?>" aria-label="Search">
                <button class="btn btn-dark" type="submit">Buscar</button>
            </form>

            <ul class="navbar-nav d-flex flex-row">
                <li class="nav-item me-3">
                    <a class="nav-link" href="minha_conta"> 
                        <i class="bi bi-person-fill" style="font-size: 1.5rem;"></i> Minha Conta
                    </a>
                </li>
                <li class="nav-item">
                    <!-- Botão do carrinho (mantido igual) -->
                    <?php echo $carrinhoView->renderizarBotaoCarrinho(); ?>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- ===========================
     FIM DA NAVBAR
=========================== -->

<!-- Carrossel -->
<div id="carouselExample" class="carousel slide" data-bs-ride="carousel">
    <div class="carousel-inner">
        <div class="carousel-item active">
            <img src="public/img/carosel1.png" class="d-block w-100" alt="Slide 1">
        </div>
        <div class="carousel-item">
            <img src="public/img/carosel2.png" class="d-block w-100" alt="Slide 2">
        </div>
        <div class="carousel-item">
            <img src="public/img/carosel3.png" class="d-block w-100" alt="Slide 3">
        </div>
    </div>
    <button class="carousel-control-prev" type="button" data-bs-target="#carouselExample" data-bs-slide="prev">
        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Previous</span>
    </button>
    <button class="carousel-control-next" type="button" data-bs-target="#carouselExample" data-bs-slide="next">
        <span class="carousel-control-next-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Next</span>
    </button>
</div>

<!-- Produtos -->
<div class="container text-center produtos">
    <!-- Linha antes da primeira fileira -->
    <hr class="linha-produtos">

    <!-- Container para produtos (carregado via AJAX) -->
    <div id="produtos-container">
        <div class="text-center py-5">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">Carregando produtos...</span>
            </div>
            <p class="mt-2">Carregando produtos...</p>
        </div>
    </div>

    <!-- Linha depois da segunda fileira -->
    <hr class="linha-produtos">
</div>

<!-- Footer -->
<footer class="footer">
    <div class="container">
        <div class="row gy-4">
            
            <!-- Logo + descrição -->
            <div class="col-lg-6 col-md-3 footer-info">
                <a href="home" class="logo align-items-center">
                    <img src="public/img/logo.png" alt="Logo">
                    <span>Brechó Koꓘero</span>
                </a>
                <p>Sua loja online de roupas, estilo e qualidade. Verde, amarelo e preto para realçar sua identidade.</p>
                <div class="social-links d-flex mt-3">
                    <a href="https://wa.me/5511992424158"><i class="bi bi-whatsapp"></i></a>
                    <a href="https://www.instagram.com/brecho.kokero?igsh=aTV4M3YyNmViZXB1"><i class="bi bi-instagram"></i></a>
                </div>
            </div>

            <!-- Links úteis -->
            <div class="col-lg-6 col-md-3 footer-links">
                <h4>Links</h4>
                <ul>
                    <li><a href="home">Início</a></li>
                    <li><a href="produtos">Produtos</a></li>
                    <li><a href="faq">FAQ</a></li>
                    <li><a href="minha_conta">Minha Conta</a></li>
                </ul>
            </div>
        </div>
    </div>

    <div class="container mt-4">
        <div class="copyright">
            &copy; 2025 <strong><span>Brechó Koꓘero</span></strong>. Todos os direitos reservados.
        </div>
        <div class="credits">
            Desenvolvido com 💛 por <a href="https://vebgtech.talentosdoifsp.gru.br/">VebgTech</a>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="public/js/home.js"></script>
<script src="public/js/script.js"></script>
</body>
</html>