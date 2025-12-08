<?php

session_start();
require_once '../../config/conexao.php';
require_once '../../model/produto.php';
require_once '../../model/produto_dao.php';
require_once '../../model/upload_imagem.php';

$conn = Conexao::getConexao();

if (($_SESSION['idNivelUsuario'] ?? 0) < 2) {
    header('Location: ../minha_conta.php');
    exit;
}

$produtoDAO = new ProdutoDAO($conn);
$uploadImagem = new UploadImagem();
$categorias = $produtoDAO->getCategorias();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $acao = $_POST['acao'] ?? '';

    if ($acao == 'adicionar') {
        try {
            $nome = trim($_POST['nome']);
            $marca = trim($_POST['marca'] ?? 'Sem Marca');
            $tamanho = trim($_POST['tamanho'] ?? '');
            $idCategoria = (int)$_POST['idCategoria'];
            $descricao = trim($_POST['descricao'] ?? '');
            $preco = (float)$_POST['preco'];
            $promocao = isset($_POST['promocao']) ? 1 : 0;
            $estoque = isset($_POST['tem_estoque']) ? max(1, (int)$_POST['estoque']) : 1;
            $estado = trim($_POST['estado']);
            $imagem = 'default.jpg';

            if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] == 0) {
                $novaImagem = $uploadImagem->upload($_FILES['imagem']);
                if ($novaImagem) {
                    $imagem = $novaImagem;
                }
            }

            $produto = new Produto(
                $nome,
                $marca,
                $tamanho,
                $estado,
                $idCategoria,
                $preco,
                $imagem,
                (bool)$promocao,
                $estoque,
                $descricao,
                false, // vendido
                null,  // data_venda
                null   // idProduto
            );

            if ($produtoDAO->criar($produto)) {
                $_SESSION['mensagem'] = '✅ Produto cadastrado com sucesso!';
                $_SESSION['tipo_mensagem'] = 'success';
            }
        } catch (Exception $e) {
            $_SESSION['mensagem'] = '❌ ' . $e->getMessage();
            $_SESSION['tipo_mensagem'] = 'error';
        }

        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }

    if ($acao == 'editar') {
        try {
            $idProduto = (int)$_POST['idProduto'];
            $imagem_atual = $_POST['imagem_atual'] ?? 'default.jpg';

            $produto = $produtoDAO->buscarPorId($idProduto);
            if (!$produto) {
                throw new Exception('Produto não encontrado.');
            }

            $produto->setNome(trim($_POST['nome']));
            $produto->setMarca(trim($_POST['marca'] ?? 'Sem Marca'));
            $produto->setTamanho(trim($_POST['tamanho'] ?? ''));
            $produto->setIdCategoria((int)$_POST['idCategoria']);
            $produto->setDescricao(trim($_POST['descricao'] ?? ''));
            $produto->setPreco((float)$_POST['preco']);
            $produto->setPromocao(isset($_POST['promocao']));
            $produto->setEstoque(isset($_POST['tem_estoque']) ? max(1, (int)$_POST['estoque']) : 1);
            $produto->setEstado(trim($_POST['estado']));

            if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] == 0) {
                $novaImagem = $uploadImagem->upload($_FILES['imagem'], $imagem_atual);
                if ($novaImagem) {
                    $produto->setImagem($novaImagem);
                }
            }
            if ($produtoDAO->atualizar($produto)) {
                $_SESSION['mensagem'] = '✅ Produto atualizado com sucesso!';
                $_SESSION['tipo_mensagem'] = 'success';
            }
        } catch (Exception $e) {
            $_SESSION['mensagem'] = '❌ ' . $e->getMessage();
            $_SESSION['tipo_mensagem'] = 'error';
        }

        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }

    if ($acao == 'excluir') {
        try {
            $idProduto = (int)$_POST['idProduto'];
            $resultado = $produtoDAO->deletar($idProduto);

            if ($resultado['result']) {
                if ($resultado['imagem'] && $resultado['imagem'] != 'default.jpg') {
                    $uploadImagem->removerImagem($resultado['imagem']);
                }

                $_SESSION['mensagem'] = '✅ Produto excluído com sucesso!';
                $_SESSION['tipo_mensagem'] = 'success';
            }
        } catch (Exception $e) {
            $_SESSION['mensagem'] = '❌ ' . $e->getMessage();
            $_SESSION['tipo_mensagem'] = 'error';
        }

        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }

    if ($acao == 'vender') {
        try {
            $idProduto = (int)$_POST['idProduto'];

            if ($produtoDAO->marcarComoVendido($idProduto)) {
                $_SESSION['mensagem'] = '✅ Produto marcado como vendido!';
                $_SESSION['tipo_mensagem'] = 'success';
            }
        } catch (Exception $e) {
            $_SESSION['mensagem'] = '❌ ' . $e->getMessage();
            $_SESSION['tipo_mensagem'] = 'error';
        }

        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}

$filtro_estado = $_GET['filtro_estado'] ?? '';
$filtro_tamanho = $_GET['filtro_tamanho'] ?? '';
$filtro_marca = $_GET['filtro_marca'] ?? '';
$filtro_categoria = $_GET['filtro_categoria'] ?? '';


$filtros = [];
if ($filtro_estado) $filtros['estado'] = $filtro_estado;
if ($filtro_tamanho) $filtros['tamanho'] = $filtro_tamanho;
if ($filtro_marca) $filtros['marca'] = $filtro_marca;
if ($filtro_categoria) $filtros['categoria'] = $filtro_categoria;

$filtros['incluir_vendidos'] = true;
$produtos = $produtoDAO->listar($filtros);

$estados = $produtoDAO->getOpcoesFiltro('estado');
$tamanho = $produtoDAO->getOpcoesFiltro('tamanho');
$marcas = $produtoDAO->getOpcoesFiltro('marca');
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Painel do Administrador - Produtos</title>
    <link rel="icon" type="image/png" href="../../../public/img/logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../../../public/css/estilo.css">
</head>

<body>
    <header class="admin-header">
        <div class="container">
            <h2><i class="bi bi-speedometer2"></i> Painel do Administrador</h2>
            <p>Gerencie seus produtos de forma completa e organizada</p>
        </div>
    </header>

    <!-- ===========================
     NAVBAR SIMPLIFICADA
=========================== -->
    <nav class="navbar navbar-expand-lg header-nav-bar">
        <div class="container">
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNavbar" aria-controls="adminNavbar" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="adminNavbar">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../../../public/index.php">
                            <i class="bi bi-house me-1"></i> Início
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="../faq.php">
                            <i class="bi bi-question-circle me-1"></i> FAQ
                        </a>
                    </li>

                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="dropdownMenu" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-tags-fill me-1"></i>
                            Produtos
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="dropdownMenu">
                            <li><a class="dropdown-item" href="../produtos.php#novidade">Novidade</a></li>
                            <li><a class="dropdown-item" href="../produtos.php#todos">Todos</a></li>
                            <li><a class="dropdown-item" href="../produtos.php#promocoes">Promoções</a></li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li><a class="dropdown-item" href="../produtos.php#bermudas-shorts">Bermudas e Shorts</a></li>
                            <li><a class="dropdown-item" href="../produtos.php#blazers">Blazers</a></li>
                            <li><a class="dropdown-item" href="../produtos.php#blusas-camisas">Blusas e Camisas</a></li>
                            <li><a class="dropdown-item" href="../produtos.php#calcas">Calças</a></li>
                            <li><a class="dropdown-item" href="../produtos.php#casacos-jaquetas">Casacos e Jaquetas</a></li>
                            <li><a class="dropdown-item" href="../produtos.php#conjuntos">Conjuntos</a></li>
                            <li><a class="dropdown-item" href="../produtos.php#saias">Saias</a></li>
                            <li><a class="dropdown-item" href="../produtos.php#sapatos">Sapatos</a></li>
                            <li><a class="dropdown-item" href="../produtos.php#social">Social</a></li>
                            <li><a class="dropdown-item" href="../produtos.php#vestidos">Vestidos</a></li>
                        </ul>
                    </li>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../minha_conta.php">
                            <i class="bi bi-person-fill"></i> Minha Conta
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- ===========================
     CONTEÚDO PRINCIPAL
=========================== -->
    <div class="container my-5">
        <?php if (isset($_SESSION['mensagem'])): ?>
            <div class="alert alert-<?= $_SESSION['tipo_mensagem'] === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show" role="alert">
                <?= $_SESSION['mensagem'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php
            unset($_SESSION['mensagem']);
            unset($_SESSION['tipo_mensagem']);
            ?>
        <?php endif; ?>

        <!-- SEÇÃO: CADASTRO DE NOVO PRODUTO -->
        <div class="section-header">
            <h4><i class="bi bi-plus-circle"></i> Cadastrar Novo Produto</h4>
        </div>
        <div class="card p-4 shadow-sm mb-5">
            <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" enctype="multipart/form-data" id="form-adicionar">
                <input type="hidden" name="acao" value="adicionar">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Nome do Produto *</label>
                        <input type="text" name="nome" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Tem Marca?</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="tem_marca" value="1" id="tem_marca_add">
                            <label class="form-check-label" for="tem_marca_add">Marcar se tiver marca</label>
                        </div>
                        <div class="marca mt-2" id="marca_add" style="display: none;">
                            <label class="form-label">Marca:</label>
                            <input type="text" name="marca" class="form-control" value="Sem Marca">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Tamanho</label>
                        <input type="text" name="tamanho" class="form-control" placeholder="P, M, G, etc.">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Estado *</label>
                        <select name="estado" class="form-select" required>
                            <option value="">Selecione...</option>
                            <option value="Novo">Novo</option>
                            <option value="Semi-novo">Semi-novo</option>
                            <option value="Usado">Usado</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Categoria *</label>
                        <select name="idCategoria" class="form-select" required>
                            <option value="">Selecione uma categoria</option>
                            <?php foreach ($categorias as $cat): ?>
                                <option value="<?= $cat['idCategoria'] ?>">
                                    <?= htmlspecialchars($cat['descricao']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Preço (R$) *</label>
                        <input type="number" step="0.01" name="preco" class="form-control" min="0" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Imagem</label>
                        <input type="file" name="imagem" accept="image/*" class="form-control">
                        <small class="text-muted">Apenas JPG, PNG, GIF. Máx. 5MB.</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Promoção?</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="promocao" value="1" id="promocao_add">
                            <label class="form-check-label" for="promocao_add">Marcar como promoção</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Tem Estoque?</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="tem_estoque" value="1" id="tem_estoque_add">
                            <label class="form-check-label" for="tem_estoque_add">Marcar se tem estoque (senão, produto único)</label>
                        </div>
                        <div class="estoque-quantidade mt-2" id="estoque_quantidade_add" style="display: none;">
                            <label class="form-label">Quantidade em Estoque</label>
                            <input type="number" name="estoque" class="form-control" min="1" value="1">
                        </div>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Descrição</label>
                        <textarea name="descricao" rows="3" class="form-control" placeholder="Descreva o produto..."></textarea>
                    </div>
                </div>
                <div class="text-end mt-4">
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="bi bi-save"></i> Salvar Produto
                    </button>
                </div>
            </form>
        </div>

        <!-- SEÇÃO: LISTAGEM DE PRODUTOS -->
        <div class="section-header">
            <h4><i class="bi bi-list-ul"></i> Produtos Cadastrados</h4>
        </div>
        <div class="card p-4 shadow-sm">

            <!-- FORMULÁRIO DE FILTROS -->
            <form method="GET" class="mb-3">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Filtrar por Estado</label>
                        <select name="filtro_estado" class="form-select">
                            <option value="">Todos</option>
                            <?php foreach ($estados as $e): ?>
                                <option value="<?= htmlspecialchars($e['estado']) ?>" <?= $filtro_estado == $e['estado'] ? 'selected' : '' ?>><?= htmlspecialchars($e['estado']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Filtrar por Tamanho</label>
                        <select name="filtro_tamanho" class="form-select">
                            <option value="">Todos</option>
                            <?php foreach ($tamanho as $t): ?>
                                <option value="<?= htmlspecialchars($t['tamanho']) ?>" <?= $filtro_tamanho == $t['tamanho'] ? 'selected' : '' ?>><?= htmlspecialchars($t['tamanho']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Filtrar por Marca</label>
                        <select name="filtro_marca" class="form-select">
                            <option value="">Todos</option>
                            <?php foreach ($marcas as $m): ?>
                                <option value="<?= htmlspecialchars($m['marca']) ?>" <?= $filtro_marca == $m['marca'] ? 'selected' : '' ?>><?= htmlspecialchars($m['marca']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Filtrar por Categoria</label>
                        <select name="filtro_categoria" class="form-select">
                            <option value="">Todas</option>
                            <?php foreach ($categorias as $cat): ?>
                                <option value="<?= $cat['idCategoria'] ?>" <?= $filtro_categoria == $cat['idCategoria'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['descricao']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-secondary me-2">Filtrar</button>
                        <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="btn btn-outline-secondary">Limpar</a>
                    </div>
                </div>
            </form>
            <!-- TABELA DE PRODUTOS -->
            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Imagem</th>
                            <th>Nome</th>
                            <th>Categoria</th>
                            <th>Preço</th>
                            <th>Estoque</th>
                            <th>Estado</th>
                            <th>Promoção</th>
                            <th>Vendido</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($produtos)): ?>
                            <?php foreach ($produtos as $produto): ?>
                                <tr>
                                    <td><?= $produto->getIdProduto() ?></td>
                                    <td>
                                        <img src="../../../public/img/<?= htmlspecialchars($produto->getImagem()) ?>"
                                            alt="Img <?= htmlspecialchars($produto->getNome()) ?>"
                                            onerror="this.src='../../../public/img/default.jpg';">
                                    </td>
                                    <td><?= htmlspecialchars($produto->getNome()) ?></td>
                                    <td><?= htmlspecialchars($produto->getNomeCategoria()) ?></td>
                                    <td>R$ <?= number_format($produto->getPreco(), 2, ',', '.') ?></td>
                                    <td><?= $produto->getStatusEstoqueHTML() ?></td>
                                    <td><?= htmlspecialchars($produto->getEstado()) ?></td>
                                    <td><?= $produto->getPromocaoHTML() ?></td>
                                    <td><?= $produto->getVendidoHTML() ?></td>
                                    <td>
                                        <?php if (!$produto->isVendido()): ?>
                                            <!-- Botões de ação para produtos não vendidos -->
                                            <div class="btn-group-vertical btn-group-sm">
                                                <button type="button" class="btn btn-warning btn-sm editar-btn"
                                                    data-id="<?= $produto->getIdProduto() ?>"
                                                    data-nome="<?= htmlspecialchars($produto->getNome()) ?>"
                                                    data-marca="<?= htmlspecialchars($produto->getMarca()) ?>"
                                                    data-tamanho="<?= htmlspecialchars($produto->getTamanho()) ?>"
                                                    data-idcategoria="<?= $produto->getIdCategoria() ?>"
                                                    data-descricao="<?= htmlspecialchars($produto->getDescricao()) ?>"
                                                    data-preco="<?= $produto->getPreco() ?>"
                                                    data-promocao="<?= $produto->isPromocao() ? 1 : 0 ?>"
                                                    data-imagem="<?= htmlspecialchars($produto->getImagem()) ?>"
                                                    data-estoque="<?= $produto->getEstoque() ?>"
                                                    data-estado="<?= htmlspecialchars($produto->getEstado()) ?>">
                                                    <i class="bi bi-pencil"></i> Editar
                                                </button>
                                                <button type="button" class="btn btn-danger btn-sm excluir-btn"
                                                    data-id="<?= $produto->getIdProduto() ?>"
                                                    data-nome="<?= htmlspecialchars($produto->getNome()) ?>">
                                                    <i class="bi bi-trash"></i> Excluir
                                                </button>
                                                <button type="button" class="btn btn-success btn-sm vender-btn"
                                                    data-id="<?= $produto->getIdProduto() ?>"
                                                    data-nome="<?= htmlspecialchars($produto->getNome()) ?>">
                                                    <i class="bi bi-check-circle"></i> Vender
                                                </button>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted">Vendido</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="10" class="text-center py-4">
                                    <p class="text-muted">Nenhum produto cadastrado ainda. Adicione o primeiro!</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- MODAL DE EDIÇÃO -->
    <div class="modal fade" id="modalEditar" tabindex="-1" aria-labelledby="modalEditarLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title" id="modalEditarLabel">
                        <i class="bi bi-pencil"></i> Editar Produto
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" enctype="multipart/form-data" id="form-editar">
                    <input type="hidden" name="acao" value="editar">
                    <input type="hidden" name="idProduto" id="editar_idProduto">
                    <input type="hidden" name="imagem_atual" id="editar_imagem_atual">
                    <div class="modal-body">
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Imagem Atual:</label>
                            <img id="preview_imagem_atual" src="" alt="Imagem atual" style="display: none; max-width: 200px; max-height: 200px; border-radius: 8px;" class="d-block mb-2">
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nome do Produto *</label>
                                <input type="text" name="nome" id="editar_nome" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tem Marca?</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="tem_marca" value="1" id="editar_tem_marca">
                                    <label class="form-check-label" for="editar_tem_marca">Marcar se tiver marca</label>
                                </div>
                                <div class="marca mt-2" id="editar_marca_div" style="display: none;">
                                    <label class="form-label">Marca:</label>
                                    <input type="text" name="marca" id="editar_marca" class="form-control" value="Sem Marca">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Tamanho</label>
                                <input type="text" name="tamanho" id="editar_tamanho" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Estado *</label>
                                <select name="estado" id="editar_estado" class="form-select" required>
                                    <option value="">Selecione...</option>
                                    <option value="Novo">Novo</option>
                                    <option value="Semi-novo">Semi-novo</option>
                                    <option value="Usado">Usado</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Categoria *</label>
                                <select name="idCategoria" id="editar_idCategoria" class="form-select" required>
                                    <option value="">Selecione uma categoria</option>
                                    <?php foreach ($categorias as $cat): ?>
                                        <option value="<?= $cat['idCategoria'] ?>">
                                            <?= htmlspecialchars($cat['descricao']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Preço (R$) *</label>
                                <input type="number" step="0.01" name="preco" id="editar_preco" class="form-control" min="0" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nova Imagem (Opcional)</label>
                                <input type="file" name="imagem" accept="image/*" class="form-control">
                                <small class="text-muted">Deixe em branco para manter a imagem atual.</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Promoção?</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="promocao" value="1" id="editar_promocao">
                                    <label class="form-check-label" for="editar_promocao">Marcar como promoção</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tem Estoque?</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="tem_estoque" value="1" id="editar_tem_estoque">
                                    <label class="form-check-label" for="editar_tem_estoque">Marcar se tem estoque (senão, produto único)</label>
                                </div>
                                <div class="estoque-quantidade mt-2" id="editar_estoque_quantidade" style="display: none;">
                                    <label class="form-label">Quantidade em Estoque</label>
                                    <input type="number" name="estoque" id="editar_estoque" class="form-control" min="1" value="1">
                                </div>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Descrição</label>
                                <textarea name="descricao" id="editar_descricao" rows="3" class="form-control"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-warning">
                            <i class="bi bi-save"></i> Atualizar Produto
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ====================== FOOTER ====================== -->
    <footer class="footer">
        <div class="container">
            <div class="row gy-4">

                <!-- Logo + descrição -->
                <div class="col-lg-6 col-md-3 footer-info">
                    <a href="../../../public/index.php" class="logo align-items-center">
                        <img src="../../../public/img/logo.png" alt="Logo">
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
                        <li><a href="../../../public/index.php">Início</a></li>
                        <li><a href="../produtos.php">Produtos</a></li>
                        <li><a href="../faq.php">FAQ</a></li>
                        <li><a href="../minha_conta.php">Minha Conta</a></li>
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
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            function toggleEstoqueQuantidade(checkboxId, quantidadeId) {
                const checkbox = document.getElementById(checkboxId);
                const quantidadeDiv = document.getElementById(quantidadeId);

                if (checkbox && quantidadeDiv) {
                    const quantidadeInput = quantidadeDiv.querySelector('input[name="estoque"]');

                    if (checkbox.checked) {
                        quantidadeDiv.style.display = 'block';
                        if (quantidadeInput) {
                            quantidadeInput.required = true;
                            quantidadeInput.value = quantidadeInput.value || '2';
                        }
                    } else {
                        quantidadeDiv.style.display = 'none';
                        if (quantidadeInput) {
                            quantidadeInput.required = false;
                            quantidadeInput.value = '1';
                        }
                    }
                }
            }

            function toggleMarcaCampo(checkboxId, campoMarcaId) {
                const checkbox = document.getElementById(checkboxId);
                const marcaDiv = document.getElementById(campoMarcaId);

                if (checkbox && marcaDiv) {
                    const marcaInput = marcaDiv.querySelector('input[name="marca"]');

                    if (checkbox.checked) {
                        marcaDiv.style.display = 'block';
                        if (marcaInput) {
                            marcaInput.required = true;
                            marcaInput.value = marcaInput.value || '';
                        }
                    } else {
                        marcaDiv.style.display = 'none';
                        if (marcaInput) {
                            marcaInput.required = false;
                            marcaInput.value = 'Sem Marca';
                        }
                    }
                }
            }

            function inicializarCampos() {
                const temEstoqueAdd = document.getElementById('tem_estoque_add');
                if (temEstoqueAdd) {
                    toggleEstoqueQuantidade('tem_estoque_add', 'estoque_quantidade_add');
                    temEstoqueAdd.addEventListener('change', function() {
                        toggleEstoqueQuantidade('tem_estoque_add', 'estoque_quantidade_add');
                    });
                }

                const temMarcaAdd = document.getElementById('tem_marca_add');
                if (temMarcaAdd) {
                    toggleMarcaCampo('tem_marca_add', 'marca_add');
                    temMarcaAdd.addEventListener('change', function() {
                        toggleMarcaCampo('tem_marca_add', 'marca_add');
                    });
                }

                const temEstoqueEdit = document.getElementById('editar_tem_estoque');
                if (temEstoqueEdit) {
                    temEstoqueEdit.addEventListener('change', function() {
                        toggleEstoqueQuantidade('editar_tem_estoque', 'editar_estoque_quantidade');
                    });
                }

                const temMarcaEdit = document.getElementById('editar_tem_marca');
                if (temMarcaEdit) {
                    temMarcaEdit.addEventListener('change', function() {
                        toggleMarcaCampo('editar_tem_marca', 'editar_marca_div');
                    });
                }
            }

            inicializarCampos();
            const modalEditarEl = document.getElementById('modalEditar');
            if (modalEditarEl) {
                const modalEditar = new bootstrap.Modal(modalEditarEl);
                const editButtons = document.querySelectorAll('.editar-btn');

                editButtons.forEach(button => {
                    button.addEventListener('click', function() {
                        const data = this.dataset;

                        document.getElementById('editar_idProduto').value = data.id || '';
                        document.getElementById('editar_imagem_atual').value = data.imagem || 'default.jpg';
                        document.getElementById('editar_nome').value = data.nome || '';

                        const marcaInput = document.getElementById('editar_marca');
                        const temMarcaCheckbox = document.getElementById('editar_tem_marca');
                        if (marcaInput && temMarcaCheckbox) {
                            marcaInput.value = data.marca || 'Sem Marca';
                            temMarcaCheckbox.checked = (data.marca && data.marca !== '' && data.marca !== 'Sem Marca');
                            toggleMarcaCampo('editar_tem_marca', 'editar_marca_div');
                        }

                        document.getElementById('editar_tamanho').value = data.tamanho || '';
                        document.getElementById('editar_idCategoria').value = data.idcategoria || '';
                        document.getElementById('editar_descricao').value = data.descricao || '';
                        document.getElementById('editar_preco').value = data.preco || '';
                        const promocaoCheckbox = document.getElementById('editar_promocao');
                        if (promocaoCheckbox) {
                            promocaoCheckbox.checked = (data.promocao == '1' || data.promocao == 1);
                        }
                        document.getElementById('editar_estoque').value = data.estoque || 1;
                        document.getElementById('editar_estado').value = data.estado || '';
                        const temEstoqueCheckbox = document.getElementById('editar_tem_estoque');
                        const estoqueQuantidade = parseInt(data.estoque) || 1;
                        if (temEstoqueCheckbox) {
                            temEstoqueCheckbox.checked = estoqueQuantidade > 1;
                            toggleEstoqueQuantidade('editar_tem_estoque', 'editar_estoque_quantidade');
                        }
                        const previewImg = document.getElementById('preview_imagem_atual');
                        const imagemAtual = data.imagem || 'default.jpg';
                        if (previewImg) {
                            previewImg.src = '../../../public/img/' + imagemAtual;
                            previewImg.style.display = 'block';
                            previewImg.onerror = function() {
                                this.src = '../../../public/img/default.jpg';
                            };
                        }
                        modalEditar.show();
                    });
                });
            }
            const deleteButtons = document.querySelectorAll('.excluir-btn');
            deleteButtons.forEach(button => {
                button.addEventListener('click', function(event) {
                    event.preventDefault();
                    const id = this.dataset.id;
                    const nome = this.dataset.nome;

                    if (confirm(`Tem certeza que deseja excluir o produto "${nome}"? Esta ação não pode ser desfeita.`)) {
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = '<?php echo $_SERVER['PHP_SELF']; ?>';
                        form.style.display = 'none';
                        form.innerHTML = `
                    <input type="hidden" name="acao" value="excluir">
                    <input type="hidden" name="idProduto" value="${id}">
                `;
                        document.body.appendChild(form);
                        form.submit();
                    }
                });
            });

            const sellButtons = document.querySelectorAll('.vender-btn');
            sellButtons.forEach(button => {
                button.addEventListener('click', function(event) {
                    event.preventDefault();
                    const id = this.dataset.id;
                    const nome = this.dataset.nome;

                    if (confirm(`Marcar "${nome}" como vendido?`)) {
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = '<?php echo $_SERVER['PHP_SELF']; ?>';
                        form.style.display = 'none';
                        form.innerHTML = `
                    <input type="hidden" name="acao" value="vender">
                    <input type="hidden" name="idProduto" value="${id}">
                `;
                        document.body.appendChild(form);
                        form.submit();
                    }
                });
            });
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }, 5000);
            });
        });
    </script>

</body>
</html>