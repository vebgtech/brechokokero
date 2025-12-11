<?php
require_once 'app/config/conexao.php';
require_once 'app/model/carrinho.php';
require_once 'app/view/carrinho_view.php';
require_once 'app/controller/carrinho_controller.php';
$conn = Conexao::getConexao();

$carrinhoController = new CarrinhoController($conn);
$carrinho = $carrinhoController->getCarrinho();
$carrinhoView = new CarrinhoView($carrinho);
echo $carrinhoView->renderizarOffcanvas();

$termo_busca = $_GET['busca'] ?? '';
$filtro_estado = $_GET['filtro_estado'] ?? '';
$filtro_tamanho = $_GET['filtro_tamanho'] ?? '';
$filtro_marca = $_GET['filtro_marca'] ?? '';

function buscarProdutos($conn, $idCategoria, $termo_busca = '', $filtro_estado = '', $filtro_tamanho = '', $filtro_marca = '')
{
  $sql = "SELECT p.* FROM produtos p WHERE (p.vendido=0 OR (p.vendido=1 AND p.data_venda > DATE_SUB(NOW(), INTERVAL 7 DAY))) ";
  $params = [];

  if ($idCategoria != 0) {
    $sql .= "AND p.idCategoria = ? ";
    $params[] = $idCategoria;
  }
  if (!empty($termo_busca)) {
    $sql .= "AND p.nome LIKE ? ";
    $params[] = "%$termo_busca%";
  }
  if (!empty($filtro_estado)) {
    $sql .= "AND p.estado = ? ";
    $params[] = $filtro_estado;
  }
  if (!empty($filtro_tamanho)) {
    $sql .= "AND p.tamanho = ? ";
    $params[] = $filtro_tamanho;
  }
  if (!empty($filtro_marca)) {
    $sql .= "AND p.marca = ? ";
    $params[] = $filtro_marca;
  }
  $sql .= "ORDER BY p.idProduto DESC";

  $stmt = $conn->prepare($sql);
  $stmt->execute($params);
  return $stmt;
}

$estados = $conn->query("SELECT DISTINCT estado FROM produtos WHERE estado IS NOT NULL AND vendido=0 ORDER BY estado");
$tamanho = $conn->query("SELECT DISTINCT tamanho FROM produtos WHERE tamanho IS NOT NULL AND vendido=0 ORDER BY tamanho");
$marcas = $conn->query("SELECT DISTINCT marca FROM produtos WHERE marca IS NOT NULL AND vendido=0 ORDER BY marca");

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

$stmt_promocao = buscarProdutos($conn, 0, $termo_busca, $filtro_estado, $filtro_tamanho, $filtro_marca);
$produtos_promocao_filtrados = [];
while ($p = $stmt_promocao->fetch()) {
  if ($p['promocao'] == 1) {
    $produtos_promocao_filtrados[] = $p;
  }
}
$produtos_promocao = $produtos_promocao_filtrados;
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
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
  <link rel="stylesheet" href="public/css/estilo.css">
</head>
<!-- Navbar -->
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
            <li>
              <hr class="dropdown-divider">
            </li>
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
      </ul>

      <form class="d-flex me-3" role="search" method="GET" action="produtos">
        <input class="form-control me-2" type="search" name="busca" placeholder="Buscar produtos..."
          value="<?php echo htmlspecialchars($termo_busca); ?>" aria-label="Search">
        <button class="btn btn-dark" type="submit">Buscar</button>
      </form>

      <ul class="navbar-nav d-flex flex-row">
        <li class="nav-item me-3">
          <a class="nav-link" href="minha_conta"> <i class="bi bi-person-fill" style="font-size: 1.5rem;"></i> Minha Conta</a>
        </li>
                    <li class="nav-item">
                        <?php echo $carrinhoView->renderizarBotaoCarrinho(); ?>
                    </li>
      </ul>
    </div>
  </div>
</nav>


<!-- ======= Cadastro ======= -->

<section id="contact" class="contact">
  <div class="container" data-aos="fade-up">
    <div id="col-lg-8">

      <form action="processar_cadastro" method="POST" enctype="multipart/form-data" role="form" class="php-email-form">
        <h1>Cadastro</h1>
        <label for="nome">Nome:</label>
        <input type="text" class="form-control" id="nome" name="nome" placeholder="Exemplo: Seu nome" required><br><br>
        <label for="email">Email:</label>
        <input type="email" class="form-control" id="email" name="email" placeholder="Exemplo: user@gmail.com" required><br><br>
        <div class="mb-3">
          <label for="senha" class="form-label">Senha:</label>
          <div class="input-group password-field">
            <input type="password" class="form-control" id="senha" name="senha"
              placeholder="Exemplo: SenhaSegura123" required>
            <button type="button" id="toggleSenha" class="btn btn-outline-secondary"
              aria-label="Mostrar senha"
              aria-pressed="false">
              <i class="fas fa-eye"></i>
            </button>
          </div>
        </div>

        <input type="submit" name="cadastro" id="butao" value="Cadastrar usuário">
        <br><br><br>
        <div class="text-center p-t-115">
          <span class="txt1">
            Já tem conta?
          </span>

          <a class="txt2" href="login">Faça seu login!</a>
        </div>

      </form>
    </div>
  </div>
  </div>
</section>

<!-- End Cadastro -->

<footer class="footer">
  <div class="container">
    <div class="row gy-4">
      <div class="col-lg-6 col-md-3 footer-info">
        <a href="" class="logo align-items-center">
          <img src="public/img/logo.png" alt="Logo">
          <span>Brechó Koꓘero</span>
        </a>
        <p>Sua loja online de roupas, estilo e qualidade. Verde, amarelo e preto para realçar sua identidade.</p>
        <div class="social-links d-flex mt-3">
          <a href="https://wa.me/5511992424158"><i class="bi bi-whatsapp"></i></a>
          <a href="https://www.instagram.com/brecho.kokero?igsh=aTV4M3YyNmViZXB1"><i class="bi bi-instagram"></i></a>
        </div>
      </div>
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
<script src="public/js/script.js"></script>


</body>

</html>