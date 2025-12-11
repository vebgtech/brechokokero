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
    if (!empty($params)) {
        $stmt->execute($params);
    } else {
        $stmt->execute();
    }
    return $stmt;
}

$estados = $conn->query("SELECT DISTINCT estado FROM produtos WHERE estado IS NOT NULL AND vendido=0 ORDER BY estado")->fetchAll(PDO::FETCH_ASSOC);
$tamanho = $conn->query("SELECT DISTINCT tamanho FROM produtos WHERE tamanho IS NOT NULL AND vendido=0 ORDER BY tamanho")->fetchAll(PDO::FETCH_ASSOC);
$marcas = $conn->query("SELECT DISTINCT marca FROM produtos WHERE marca IS NOT NULL AND vendido=0 ORDER BY marca")->fetchAll(PDO::FETCH_ASSOC);

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
$produtos_promocao_todos = $stmt_promocao->fetchAll(PDO::FETCH_ASSOC);
$produtos_promocao_filtrados = [];
foreach ($produtos_promocao_todos as $p) {
    if ($p['promocao'] == 1) {
        $produtos_promocao_filtrados[] = $p;
    }
}
$produtos_promocao = $produtos_promocao_filtrados; 
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="public/img/logo.png">
    <title>FAQ - Brechó Kokero</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="public/css/estilo.css">
</head>
<body>
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

<main class="container my-5">
    <div class="text-center mb-5">
        <h1 class="faq-main-title">TIRE SUAS DÚVIDAS SOBRE</h1>
    </div>

    <nav class="faq-nav-buttons">
        <a href="#sobre" class="btn-faq">Sobre os Produtos</a>
        <a href="#compra" class="btn-faq">Compra e Pagamento</a>
        <a href="#envio" class="btn-faq">Envio e Entrega</a>
        <a href="#trocas" class="btn-faq">Trocas e Devoluções</a>
        <a href="#tamanhos" class="btn-faq">Tamanhos e Medidas</a>
        <a href="#loja" class="btn-faq">Loja Física</a>
        <a href="#contato" class="btn-faq">Suporte e Contato</a>
    </nav>

    <div class="content-faq">
        <section id="sobre" class="faq-item">
            <div class="section-title-container">
                <hr class="flex-grow-1">
                <h2 class="mx-3">Sobre os Produtos</h2>
                <hr class="flex-grow-1">
            </div>
            <h3>Os produtos são originais?</h3>
            <p>Sim! Trabalhamos exclusivamente com produtos 100% originais, garantindo autenticidade e qualidade em cada peça.</p>
            <h3>Os produtos são novos ou usados?</h3>
            <p>Nossa curadoria é composta por itens vintage e seminovos, todos cuidadosamente selecionados e higienizados antes de serem disponibilizados para venda.</p>
            <h3>As peças apresentam sinais de uso?</h3>
            <p>Por serem vintage, algumas peças podem apresentar leves marcas do tempo, o que faz parte da história e autenticidade de cada item. Caso haja algum detalhe relevante (manchas, furos, desgastes), ele será informado na descrição do produto e ilustrado nas fotos.</p>
        </section>
        
        <section id="compra" class="faq-item">
            <div class="section-title-container">
                <hr class="flex-grow-1">
                <h2 class="mx-3">Compra e Pagamento</h2>
                <hr class="flex-grow-1">
            </div>
            <h3>Posso comprar diretamente pelo WhatsApp?</h3>
            <p>Sim. Todas as compras são feitas exclusivamente pelo nosso whatsapp.</p>
            <h3>Quais são as formas de pagamento?</h3>
            <p>Aceitamos somente Pix, proporcionando praticidade e segurança na sua compra.</p>
        </section>
        
        <section id="envio" class="faq-item">
             <div class="section-title-container">
                <hr class="flex-grow-1">
                <h2 class="mx-3">Envio e Entrega</h2>
                <hr class="flex-grow-1">
            </div>
            <h3>Vocês fazem entrega em estações de metrô/trem?</h3>
            <p>Não fazemos entregas pessoais. Todas as entregas são realizadas exclusivamente via transportadora para garantir maior segurança e rastreamento.</p>
            <h3>Como calcular o frete e prazo de entrega?</h3>
            <p>Para calcular o frete e prazo de entrega, basta selecionar o produto desejado e inserir seu CEP no campo correspondente.</p>
        </section>

        <section id="trocas" class="faq-item">
            <div class="section-title-container">
                <hr class="flex-grow-1">
                <h2 class="mx-3">Trocas e Devoluções</h2>
                <hr class="flex-grow-1">
            </div>
            <h3>Vocês fazem trocas/devoluções?</h3>
            <p>Não efetuamos trocas nem devoluções. Após a compra, não é possível realizar qualquer alteração no pedido.</p>
        </section>

        <section id="tamanhos" class="faq-item">
            <div class="section-title-container">
                <hr class="flex-grow-1">
                <h2 class="mx-3">Tamanhos e Medidas</h2>
                <hr class="flex-grow-1">
            </div>
            <h3>O tamanho informado na etiqueta é confiável?</h3>
            <p>Nem sempre! Como trabalhamos com diferentes marcas e épocas, os tamanhos podem variar. Por isso, informamos todas as dimensões reais na descrição do produto.</p>
        </section>

         <section id="loja" class="faq-item">
            <div class="section-title-container">
                <hr class="flex-grow-1">
                <h2 class="mx-3">Loja Física</h2>
                <hr class="flex-grow-1">
            </div>
            <h3>Vocês possuem loja física?</h3>
            <p>Não temos uma loja física. Todas as compras são feitas via WhatsApp.</p>
        </section>

        <section id="contato" class="faq-item">
            <div class="section-title-container">
                <hr class="flex-grow-1">
                <h2 class="mx-3">Suporte e Contato</h2>
                <hr class="flex-grow-1">
            </div>
            <p>Se ainda tiver dúvidas, nossa equipe está sempre pronta para te ajudar! Entre em contato por:</p>
            <p><strong>WhatsApp:</strong> <a href="tel:+5511992424158" class="contact-link">+55 11 99242-4158</a></p>
            <p><strong>Instagram:</strong> <a href="https://instagram.com/brecho.kokero" target="_blank" class="contact-link">@brecho.kokero</a></p>
        </section>
    </div>
</main>

<footer class="footer">
    <div class="container">
        <div class="row gy-4">
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