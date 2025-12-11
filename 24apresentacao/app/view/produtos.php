<?php
require_once 'app/config/conexao.php';
require_once 'app/model/carrinho.php';
require_once 'app/view/carrinho_view.php';
require_once 'app/controller/carrinho_controller.php';

$conn = Conexao::getConexao();

$termo_busca = $_GET['busca'] ?? '';
$filtro_estado = $_GET['filtro_estado'] ?? '';
$filtro_tamanho = $_GET['filtro_tamanho'] ?? '';
$filtro_marca = $_GET['filtro_marca'] ?? '';

$carrinhoController = new CarrinhoController($conn);
$carrinho = $carrinhoController->getCarrinho();
$carrinhoView = new CarrinhoView($carrinho);

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
echo $carrinhoView->renderizarOffcanvas();
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="public/img/logo.png">
    <title>Produtos - Brechó Kokero</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
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

    <div class="produtos container">
        <div class="filtros">
            <h4 class="text-center mb-4" style="color: #9ed06f;">Filtrar Produtos</h4>
            <form method="GET" action="produtos.php">
                <input type="hidden" name="busca" value="<?php echo htmlspecialchars($termo_busca); ?>">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Estado</label>
                        <select name="filtro_estado" class="form-select">
                            <option value="">Todos</option>
                            <?php foreach ($estados as $e): ?>
                                <option value="<?= htmlspecialchars($e['estado']) ?>" <?= $filtro_estado == $e['estado'] ? 'selected' : '' ?>><?= htmlspecialchars($e['estado']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Tamanho</label>
                        <select name="filtro_tamanho" class="form-select">
                            <option value="">Todos</option>
                            <?php foreach ($tamanho as $t): ?>
                                <option value="<?= htmlspecialchars($t['tamanho']) ?>" <?= $filtro_tamanho == $t['tamanho'] ? 'selected' : '' ?>><?= htmlspecialchars($t['tamanho']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Marca</label>
                        <select name="filtro_marca" class="form-select">
                            <option value="">Todos</option>
                            <?php foreach ($marcas as $m): ?>
                                <option value="<?= htmlspecialchars($m['marca']) ?>" <?= $filtro_marca == $m['marca'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($m['marca']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn">Aplicar Filtros</button>
                    </div>
                </div>
            </form>
        </div>

        <h2>Nossos Produtos</h2>
        <div class="categoria" id="promocoes">
            <h3>Promoções</h3>
            <?php if (count($produtos_promocao) > 0): ?>
                <div class="row row-cols-1 row-cols-md-3 g-4">
                    <?php foreach ($produtos_promocao as $p): ?>
                        <div class="col">
                            <div class="card">
                                <img src="public/img/<?= htmlspecialchars($p['imagem'] ?? 'default.jpg'); ?>" class="card-img-top"
                                    alt="<?= htmlspecialchars($p['nome']); ?>" onerror="this.src='../../public/img/default.jpg';"
                                    onerror="this.src='public/img/default.jpg';">
                                <div class="card-body">
                                    <h5 class="card-title">
                                        <?= htmlspecialchars($p['nome']); ?> -
                                        <?= htmlspecialchars($p['marca'] ?? ''); ?>
                                    </h5>
                                    <p class="card-text">
                                        <?= htmlspecialchars($p['descricao'] ?? ''); ?> <strong>Em Promoção!</strong>
                                    </p>
                                    <p class="preco">R$
                                        <?= number_format($p['preco'], 2, ',', '.'); ?>
                                    </p>
                                    <p class="text-muted small">Estado:
                                        <?= htmlspecialchars($p['estado'] ?? 'N/A'); ?> | Tamanho:
                                        <?= htmlspecialchars($p['tamanho'] ?? 'N/A'); ?>
                                    </p>
                                    <?php if ($p['vendido'] == 1): ?>
                                        <p class="vendido">Já foi vendido</p>
                                    <?php else: ?>
                                        <?php if ($p['estoque'] == 1): ?>
                                            <span class="badge bg-info badge-estoque">Produto Único</span>
                                        <?php elseif ($p['estoque'] > 1): ?>
                                            <span class="badge bg-success badge-estoque">Em Estoque</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger badge-estoque">Fora de Estoque</span>
                                        <?php endif; ?>
                                        <br><br>
                                        <a href="produto_detalhes?id=<?= $p['idProduto']; ?>" class="btn btn-comprar">Comprar Agora</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="sem-produtos">Nenhuma promoção ativa no momento. Fique de olho!</p>
            <?php endif; ?>
        </div>

        <?php 
        $stmt_todos = buscarProdutos($conn, 0, $termo_busca, $filtro_estado, $filtro_tamanho, $filtro_marca);
        $produtos_todos_filtrados = $stmt_todos->fetchAll(PDO::FETCH_ASSOC);
        if (count($produtos_todos_filtrados) > 0): 
        ?>
            <div class="categoria" id="todos">
                <h3>Todos</h3>
                <div class="row row-cols-1 row-cols-md-3 g-4">
                    <?php foreach($produtos_todos_filtrados as $p): ?>
                    <div class="col">
                        <div class="card">
                            <img src="public/img/<?= htmlspecialchars($p['imagem'] ?? 'default.jpg'); ?>" class="card-img-top"
                                alt="<?= htmlspecialchars($p['nome']); ?>" onerror="this.src='public/img/default.jpg';">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <?= htmlspecialchars($p['nome']); ?> -
                                    <?= htmlspecialchars($p['marca'] ?? 'Sem Marca'); ?>
                                </h5>
                                <p class="card-text">
                                    <?= htmlspecialchars($p['descricao'] ?? 'Produto de qualidade do Brechó Kokero.'); ?>
                                </p>
                                <p class="preco">R$
                                    <?= number_format($p['preco'], 2, ',', '.'); ?>
                                </p>
                                <p class="text-muted small">Estado:
                                    <?= htmlspecialchars($p['estado'] ?? 'N/A'); ?> | Tamanho:
                                    <?= htmlspecialchars($p['tamanho'] ?? 'N/A'); ?>
                                </p>
                                <?php if ($p['vendido'] == 1): ?>
                                    <p class="vendido">Já foi vendido</p>
                                <?php else: ?>
                                    <?php if ($p['estoque'] == 1): ?>
                                        <span class="badge bg-info badge-estoque">Produto Único</span>
                                    <?php elseif ($p['estoque'] > 1): ?>
                                        <span class="badge bg-success badge-estoque">Em Estoque</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger badge-estoque">Fora de Estoque</span>
                                    <?php endif; ?>
                                    <br><br>
                                    <a href="produto_detalhes?id=<?= $p['idProduto']; ?>" class="btn btn-comprar">Comprar</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="categoria">
                <h3>Todos</h3>
                <p class="sem-produtos">Nenhum produto disponível nesta categoria no momento.</p>
            </div>
        <?php endif; ?>

        <?php
        $stmt_bermudas = buscarProdutos($conn, 1, $termo_busca, $filtro_estado, $filtro_tamanho, $filtro_marca);
        $produtos_bermudas = $stmt_bermudas->fetchAll(PDO::FETCH_ASSOC);
        if (count($produtos_bermudas) > 0):
        ?>
            <div class="categoria" id="bermudas-shorts">
                <h3>Bermudas e Shorts</h3>
                <div class="row row-cols-1 row-cols-md-3 g-4">
                    <?php foreach ($produtos_bermudas as $p): ?>
                        <div class="col">
                            <div class="card">
                                <img src="public/img/<?= htmlspecialchars($p['imagem'] ?? 'default.jpg'); ?>" class="card-img-top"
                                    alt="<?= htmlspecialchars($p['nome']); ?>" onerror="this.src='public/img/default.jpg';">
                                <div class="card-body">
                                    <h5 class="card-title">
                                        <?= htmlspecialchars($p['nome']); ?> -
                                        <?= htmlspecialchars($p['marca'] ?? 'Sem Marca'); ?>
                                    </h5>
                                    <p class="card-text">
                                        <?= htmlspecialchars($p['descricao'] ?? 'Produto de qualidade do Brechó Kokero.'); ?>
                                    </p>
                                    <p class="preco">R$
                                        <?= number_format($p['preco'], 2, ',', '.'); ?>
                                    </p>
                                    <p class="text-muted small">Estado:
                                        <?= htmlspecialchars($p['estado'] ?? 'N/A'); ?> | Tamanho:
                                        <?= htmlspecialchars($p['tamanho'] ?? 'N/A'); ?>
                                    </p>
                                    <?php if ($p['vendido'] == 1): ?>
                                        <p class="vendido">Já foi vendido</p>
                                    <?php else: ?>
                                        <?php if ($p['estoque'] == 1): ?>
                                            <span class="badge bg-info badge-estoque">Produto Único</span>
                                        <?php elseif ($p['estoque'] > 1): ?>
                                            <span class="badge bg-success badge-estoque">Em Estoque</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger badge-estoque">Fora de Estoque</span>
                                        <?php endif; ?>
                                        <br><br>
                                        <a href="produto_detalhes.php?id=<?= $p['idProduto']; ?>" class="btn btn-comprar">Comprar</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php
        $stmt_blazers = buscarProdutos($conn, 2, $termo_busca, $filtro_estado, $filtro_tamanho, $filtro_marca);
        $produtos_blazers = $stmt_blazers->fetchAll(PDO::FETCH_ASSOC);
        if (count($produtos_blazers) > 0):
        ?>
            <div class="categoria" id="blazers">
                <h3>Blazers</h3>
                <div class="row row-cols-1 row-cols-md-3 g-4">
                    <?php foreach ($produtos_blazers as $p): ?>
                        <div class="col">
                            <div class="card">
                                <img src="public/img/<?= htmlspecialchars($p['imagem'] ?? 'default.jpg'); ?>" class="card-img-top"
                                    alt="<?= htmlspecialchars($p['nome']); ?>" onerror="this.src='public/img/default.jpg';">
                                <div class="card-body">
                                    <h5 class="card-title">
                                        <?= htmlspecialchars($p['nome']); ?> -
                                        <?= htmlspecialchars($p['marca'] ?? ''); ?>
                                    </h5>
                                    <p class="card-text">
                                        <?= htmlspecialchars($p['descricao'] ?? 'Produto de qualidade do Brechó Kokero.'); ?>
                                    </p>
                                    <p class="preco">R$
                                        <?= number_format($p['preco'], 2, ',', '.'); ?>
                                    </p>
                                    <p class="text-muted small">Estado:
                                        <?= htmlspecialchars($p['estado'] ?? 'N/A'); ?> | Tamanho:
                                        <?= htmlspecialchars($p['tamanho'] ?? 'N/A'); ?>
                                    </p>
                                    <?php if ($p['vendido'] == 1): ?>
                                        <p class="vendido">Já foi vendido</p>
                                    <?php else: ?>
                                        <?php if ($p['estoque'] == 1): ?>
                                            <span class="badge bg-info badge-estoque">Produto Único</span>
                                        <?php elseif ($p['estoque'] > 1): ?>
                                            <span class="badge bg-success badge-estoque">Em Estoque</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger badge-estoque">Fora de Estoque</span>
                                        <?php endif; ?>
                                        <br><br>
                                        <a href="produto_detalhes?id=<?= $p['idProduto']; ?>" class="btn btn-comprar">Comprar</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php
        $stmt_blusas = buscarProdutos($conn, 3, $termo_busca, $filtro_estado, $filtro_tamanho, $filtro_marca);
        $produtos_blusas_e_camisas = $stmt_blusas->fetchAll(PDO::FETCH_ASSOC);
        if (count($produtos_blusas_e_camisas) > 0):
        ?>
            <div class="categoria" id="blusas_camisas">
                <h3>Blusas e Camisas</h3>
                <div class="row row-cols-1 row-cols-md-3 g-4">
                    <?php foreach ($produtos_blusas_e_camisas as $p): ?>
                        <div class="col">
                            <div class="card">
                                <img src="public/img/<?= htmlspecialchars($p['imagem'] ?? 'default.jpg'); ?>" class="card-img-top"
                                    alt="<?= htmlspecialchars($p['nome']); ?>" onerror="this.src='public/img/default.jpg';">
                                <div class="card-body">
                                    <h5 class="card-title">
                                        <?= htmlspecialchars($p['nome']); ?> -
                                        <?= htmlspecialchars($p['marca'] ?? ''); ?>
                                    </h5>
                                    <p class="card-text">
                                        <?= htmlspecialchars($p['descricao'] ?? 'Produto de qualidade do Brechó Kokero.'); ?>
                                    </p>
                                    <p class="preco">R$
                                        <?= number_format($p['preco'], 2, ',', '.'); ?>
                                    </p>
                                    <p class="text-muted small">Estado:
                                        <?= htmlspecialchars($p['estado'] ?? 'N/A'); ?> | Tamanho:
                                        <?= htmlspecialchars($p['tamanho'] ?? 'N/A'); ?>
                                    </p>
                                    <?php if ($p['vendido'] == 1): ?>
                                        <p class="vendido">Já foi vendido</p>
                                    <?php else: ?>
                                        <?php if ($p['estoque'] == 1): ?>
                                            <span class="badge bg-info badge-estoque">Produto Único</span>
                                        <?php elseif ($p['estoque'] > 1): ?>
                                            <span class="badge bg-success badge-estoque">Em Estoque</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger badge-estoque">Fora de Estoque</span>
                                        <?php endif; ?>
                                        <br><br>
                                        <a href="produto_detalhes?id=<?= $p['idProduto']; ?>" class="btn btn-comprar">Comprar</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php
        $stmt_calcas = buscarProdutos($conn, 4, $termo_busca, $filtro_estado, $filtro_tamanho, $filtro_marca);
        $produtos_calcas = $stmt_calcas->fetchAll(PDO::FETCH_ASSOC);
        if (count($produtos_calcas) > 0):
        ?>
            <div class="categoria" id="calcas">
                <h3>Calças</h3>
                <div class="row row-cols-1 row-cols-md-3 g-4">
                    <?php foreach ($produtos_calcas as $p): ?>
                        <div class="col">
                            <div class="card">
                                <img src="public/img/<?= htmlspecialchars($p['imagem'] ?? 'default.jpg'); ?>" class="card-img-top"
                                    alt="<?= htmlspecialchars($p['nome']); ?>" onerror="this.src='public/img/default.jpg';">
                                <div class="card-body">
                                    <h5 class="card-title">
                                        <?= htmlspecialchars($p['nome']); ?> -
                                        <?= htmlspecialchars($p['marca'] ?? ''); ?>
                                    </h5>
                                    <p class="card-text">
                                        <?= htmlspecialchars($p['descricao'] ?? 'Produto de qualidade do Brechó Kokero.'); ?>
                                    </p>
                                    <p class="preco">R$
                                        <?= number_format($p['preco'], 2, ',', '.'); ?>
                                    </p>
                                    <p class="text-muted small">Estado:
                                        <?= htmlspecialchars($p['estado'] ?? 'N/A'); ?> | Tamanho:
                                        <?= htmlspecialchars($p['tamanho'] ?? 'N/A'); ?>
                                    </p>
                                    <?php if ($p['vendido'] == 1): ?>
                                        <p class="vendido">Já foi vendido</p>
                                    <?php else: ?>
                                        <?php if ($p['estoque'] == 1): ?>
                                            <span class="badge bg-info badge-estoque">Produto Único</span>
                                        <?php elseif ($p['estoque'] > 1): ?>
                                            <span class="badge bg-success badge-estoque">Em Estoque</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger badge-estoque">Fora de Estoque</span>
                                        <?php endif; ?>
                                        <br><br>
                                        <a href="produto_detalhes?id=<?= $p['idProduto']; ?>" class="btn btn-comprar">Comprar</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php
        $stmt_casacos = buscarProdutos($conn, 5, $termo_busca, $filtro_estado, $filtro_tamanho, $filtro_marca);
        $produtos_casacos_e_jaquetas = $stmt_casacos->fetchAll(PDO::FETCH_ASSOC);
        if (count($produtos_casacos_e_jaquetas) > 0):
        ?>
            <div class="categoria" id="casacos_e_jaquetas">
                <h3>Casacos e Jaquetas</h3>
                <div class="row row-cols-1 row-cols-md-3 g-4">
                    <?php foreach ($produtos_casacos_e_jaquetas as $p): ?>
                        <div class="col">
                            <div class="card">
                                <img src="public/img/<?= htmlspecialchars($p['imagem'] ?? 'default.jpg'); ?>" class="card-img-top"
                                    alt="<?= htmlspecialchars($p['nome']); ?>" onerror="this.src='public/img/default.jpg';">
                                <div class="card-body">
                                    <h5 class="card-title">
                                        <?= htmlspecialchars($p['nome']); ?> -
                                        <?= htmlspecialchars($p['marca'] ?? ''); ?>
                                    </h5>
                                    <p class="card-text">
                                        <?= htmlspecialchars($p['descricao'] ?? 'Produto de qualidade do Brechó Kokero.'); ?>
                                    </p>
                                    <p class="preco">R$
                                        <?= number_format($p['preco'], 2, ',', '.'); ?>
                                    </p>
                                    <p class="text-muted small">Estado:
                                        <?= htmlspecialchars($p['estado'] ?? 'N/A'); ?> | Tamanho:
                                        <?= htmlspecialchars($p['tamanho'] ?? 'N/A'); ?>
                                    </p>
                                    <?php if ($p['vendido'] == 1): ?>
                                        <p class="vendido">Já foi vendido</p>
                                    <?php else: ?>
                                        <?php if ($p['estoque'] == 1): ?>
                                            <span class="badge bg-info badge-estoque">Produto Único</span>
                                        <?php elseif ($p['estoque'] > 1): ?>
                                            <span class="badge bg-success badge-estoque">Em Estoque</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger badge-estoque">Fora de Estoque</span>
                                        <?php endif; ?>
                                        <br><br>
                                        <a href="produto_detalhes?id=<?= $p['idProduto']; ?>" class="btn btn-comprar">Comprar</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php
        $stmt_conjuntos = buscarProdutos($conn, 6, $termo_busca, $filtro_estado, $filtro_tamanho, $filtro_marca);
        $produtos_conjuntos = $stmt_conjuntos->fetchAll(PDO::FETCH_ASSOC);
        if (count($produtos_conjuntos) > 0):
        ?>
            <div class="categoria" id="conjuntos">
                <h3>Conjuntos</h3>
                <div class="row row-cols-1 row-cols-md-3 g-4">
                    <?php foreach ($produtos_conjuntos as $p): ?>
                        <div class="col">
                            <div class="card">
                                <img src="public/img/<?= htmlspecialchars($p['imagem'] ?? 'default.jpg'); ?>" class="card-img-top"
                                    alt="<?= htmlspecialchars($p['nome']); ?>" onerror="this.src='public/img/default.jpg';">
                                <div class="card-body">
                                    <h5 class="card-title">
                                        <?= htmlspecialchars($p['nome']); ?> -
                                        <?= htmlspecialchars($p['marca'] ?? ''); ?>
                                    </h5>
                                    <p class="card-text">
                                        <?= htmlspecialchars($p['descricao'] ?? 'Produto de qualidade do Brechó Kokero.'); ?>
                                    </p>
                                    <p class="preco">R$
                                        <?= number_format($p['preco'], 2, ',', '.'); ?>
                                    </p>
                                    <p class="text-muted small">Estado:
                                        <?= htmlspecialchars($p['estado'] ?? 'N/A'); ?> | Tamanho:
                                        <?= htmlspecialchars($p['tamanho'] ?? 'N/A'); ?>
                                    </p>
                                    <?php if ($p['vendido'] == 1): ?>
                                        <p class="vendido">Já foi vendido</p>
                                    <?php else: ?>
                                        <?php if ($p['estoque'] == 1): ?>
                                            <span class="badge bg-info badge-estoque">Produto Único</span>
                                        <?php elseif ($p['estoque'] > 1): ?>
                                            <span class="badge bg-success badge-estoque">Em Estoque</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger badge-estoque">Fora de Estoque</span>
                                        <?php endif; ?>
                                        <br><br>
                                        <a href="produto_detalhes?id=<?= $p['idProduto']; ?>" class="btn btn-comprar">Comprar</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php 
        $stmt_saias = buscarProdutos($conn, 7, $termo_busca, $filtro_estado, $filtro_tamanho, $filtro_marca);
        $produtos_saias = $stmt_saias->fetchAll(PDO::FETCH_ASSOC);
        if (count($produtos_saias) > 0): 
        ?>
        <div class="categoria" id="saias">
            <h3>Saias</h3>
            <div class="row row-cols-1 row-cols-md-3 g-4">
                <?php foreach($produtos_saias as $p): ?>
                            <div class="col">
                        <div class="card">
                            <img src="public/img/<?= htmlspecialchars($p['imagem'] ?? 'default.jpg'); ?>" class="card-img-top"
                                alt="<?= htmlspecialchars($p['nome']); ?>" onerror="this.src='public/img/default.jpg';">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <?= htmlspecialchars($p['nome']); ?> -
                                    <?= htmlspecialchars($p['marca'] ?? ''); ?>
                                </h5>
                                <p class="card-text">
                                    <?= htmlspecialchars($p['descricao'] ?? 'Produto de qualidade do Brechó Kokero.'); ?>
                                </p>
                                <p class="preco">R$
                                    <?= number_format($p['preco'], 2, ',', '.'); ?>
                                </p>
                                <p class="text-muted small">Estado:
                                    <?= htmlspecialchars($p['estado'] ?? 'N/A'); ?> | Tamanho:
                                    <?= htmlspecialchars($p['tamanho'] ?? 'N/A'); ?>
                                </p>
                                <?php if ($p['vendido'] == 1): ?>
                                    <p class="vendido">Já foi vendido</p>
                                <?php else: ?>
                                    <?php if ($p['estoque'] == 1): ?>
                                        <span class="badge bg-info badge-estoque">Produto Único</span>
                                    <?php elseif ($p['estoque'] > 1): ?>
                                        <span class="badge bg-success badge-estoque">Em Estoque</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger badge-estoque">Fora de Estoque</span>
                                    <?php endif; ?>
                                    <br><br>
                                    <a href="produto_detalhes?id=<?= $p['idProduto']; ?>" class="btn btn-comprar">Comprar</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php 
        $stmt_sapatos = buscarProdutos($conn, 8, $termo_busca, $filtro_estado, $filtro_tamanho, $filtro_marca);
        $produtos_sapatos = $stmt_sapatos->fetchAll(PDO::FETCH_ASSOC);
        if (count($produtos_sapatos) > 0): 
        ?>
        <div class="categoria" id="sapatos">
            <h3>Sapatos</h3>
            <div class="row row-cols-1 row-cols-md-3 g-4">
                <?php foreach($produtos_sapatos as $p): ?>
                                    <div class="col">
                        <div class="card">
                            <img src="public/img/<?= htmlspecialchars($p['imagem'] ?? 'default.jpg'); ?>" class="card-img-top"
                                alt="<?= htmlspecialchars($p['nome']); ?>" onerror="this.src='public/img/default.jpg';">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <?= htmlspecialchars($p['nome']); ?> -
                                    <?= htmlspecialchars($p['marca'] ?? ''); ?>
                                </h5>
                                <p class="card-text">
                                    <?= htmlspecialchars($p['descricao'] ?? 'Produto de qualidade do Brechó Kokero.'); ?>
                                </p>
                                <p class="preco">R$
                                    <?= number_format($p['preco'], 2, ',', '.'); ?>
                                </p>
                                <p class="text-muted small">Estado:
                                    <?= htmlspecialchars($p['estado'] ?? 'N/A'); ?> | Tamanho:
                                    <?= htmlspecialchars($p['tamanho'] ?? 'N/A'); ?>
                                </p>
                                <?php if ($p['vendido'] == 1): ?>
                                    <p class="vendido">Já foi vendido</p>
                                <?php else: ?>
                                    <?php if ($p['estoque'] == 1): ?>
                                        <span class="badge bg-info badge-estoque">Produto Único</span>
                                    <?php elseif ($p['estoque'] > 1): ?>
                                        <span class="badge bg-success badge-estoque">Em Estoque</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger badge-estoque">Fora de Estoque</span>
                                    <?php endif; ?>
                                    <br><br>
                                    <a href="produto_detalhes?id=<?= $p['idProduto']; ?>" class="btn btn-comprar">Comprar</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php 
        $stmt_social = buscarProdutos($conn, 9, $termo_busca, $filtro_estado, $filtro_tamanho, $filtro_marca);
        $produtos_social = $stmt_social->fetchAll(PDO::FETCH_ASSOC);
        if (count($produtos_social) > 0): 
        ?>
        <div class="categoria" id="social">
            <h3>Social</h3>
            <div class="row row-cols-1 row-cols-md-3 g-4">
                <?php foreach($produtos_social as $p): ?>
                    <div class="col">
                        <div class="card">
                            <img src="public/img/<?= htmlspecialchars($p['imagem'] ?? 'default.jpg'); ?>" class="card-img-top"
                                alt="<?= htmlspecialchars($p['nome']); ?>" onerror="this.src='public/img/default.jpg';">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <?= htmlspecialchars($p['nome']); ?> -
                                    <?= htmlspecialchars($p['marca'] ?? ''); ?>
                                </h5>
                                <p class="card-text">
                                    <?= htmlspecialchars($p['descricao'] ?? 'Produto de qualidade do Brechó Kokero.'); ?>
                                </p>
                                <p class="preco">R$
                                    <?= number_format($p['preco'], 2, ',', '.'); ?>
                                </p>
                                <p class="text-muted small">Estado:
                                    <?= htmlspecialchars($p['estado'] ?? 'N/A'); ?> | Tamanho:
                                    <?= htmlspecialchars($p['tamanho'] ?? 'N/A'); ?>
                                </p>
                                <?php if ($p['vendido'] == 1): ?>
                                    <p class="vendido">Já foi vendido</p>
                                <?php else: ?>
                                    <?php if ($p['estoque'] == 1): ?>
                                        <span class="badge bg-info badge-estoque">Produto Único</span>
                                    <?php elseif ($p['estoque'] > 1): ?>
                                        <span class="badge bg-success badge-estoque">Em Estoque</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger badge-estoque">Fora de Estoque</span>
                                    <?php endif; ?>
                                    <br><br>
                                    <a href="produto_detalhes?id=<?= $p['idProduto']; ?>" class="btn btn-comprar">Comprar</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php 
        $stmt_vestidos = buscarProdutos($conn, 10, $termo_busca, $filtro_estado, $filtro_tamanho, $filtro_marca);
        $produtos_vestidos = $stmt_vestidos->fetchAll(PDO::FETCH_ASSOC);
        if (count($produtos_vestidos) > 0): 
        ?>
        <div class="categoria" id="vestidos">
            <h3>Vestidos</h3>
            <div class="row row-cols-1 row-cols-md-3 g-4">
                <?php foreach($produtos_vestidos as $p): ?>
                    <div class="col">
                        <div class="card">
                            <img src="public/img/<?= htmlspecialchars($p['imagem'] ?? 'default.jpg'); ?>" class="card-img-top"
                                alt="<?= htmlspecialchars($p['nome']); ?>" onerror="this.src='public/img/default.jpg';">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <?= htmlspecialchars($p['nome']); ?> -
                                    <?= htmlspecialchars($p['marca'] ?? ''); ?>
                                </h5>
                                <p class="card-text">
                                    <?= htmlspecialchars($p['descricao'] ?? 'Produto de qualidade do Brechó Kokero.'); ?>
                                </p>
                                <p class="preco">R$
                                    <?= number_format($p['preco'], 2, ',', '.'); ?>
                                </p>
                                <p class="text-muted small">Estado:
                                    <?= htmlspecialchars($p['estado'] ?? 'N/A'); ?> | Tamanho:
                                    <?= htmlspecialchars($p['tamanho'] ?? 'N/A'); ?>
                                </p>
                                <?php if ($p['vendido'] == 1): ?>
                                    <p class="vendido">Já foi vendido</p>
                                <?php else: ?>
                                    <?php if ($p['estoque'] == 1): ?>
                                        <span class="badge bg-info badge-estoque">Produto Único</span>
                                    <?php elseif ($p['estoque'] > 1): ?>
                                        <span class="badge bg-success badge-estoque">Em Estoque</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger badge-estoque">Fora de Estoque</span>
                                    <?php endif; ?>
                                    <br><br>
                                    <a href="produto_detalhes?id=<?= $p['idProduto']; ?>" class="btn btn-comprar">Comprar</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
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
</body>

</html>