<?php
if (($_SESSION['idNivelUsuario'] ?? 0) < 2) {
    header('Location: minha_conta.php');
    exit;
}

// Verificar se a sessão está ativa
if (session_status() !== PHP_SESSION_ACTIVE) {
    die('Erro: Sessão não está ativa');
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Painel do Administrador - Produtos</title>
    <link rel="icon" type="image/png" href="../../public/img/logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="public/css/estilo.css">
    <link rel="stylesheet" href="public/css/admin.css">
    
</head>
<body>
    <header class="admin-header">
        <div class="container">
            <h2><i class="bi bi-speedometer2"></i> Painel do Administrador</h2>
            <p>Gerencie seus produtos de forma completa e organizada</p>
        </div>
    </header>

    <!-- ===========================
     NAVBAR SIMPLIFICADA CENTRALIZADA
=========================== -->
<nav class="navbar navbar-expand-lg header-nav-bar">
    <div class="container">
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNavbar" aria-controls="adminNavbar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse justify-content-center" id="adminNavbar">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" href="home">
                        <i class="bi bi-house me-1"></i> Início
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="faq">
                        <i class="bi bi-question-circle me-1"></i> FAQ
                    </a>
                </li>

                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="dropdownMenu" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-tags-fill me-1"></i>
                        Produtos
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
                
                <li class="nav-item">
                    <a class="nav-link" href="minha_conta">
                        <i class="bi bi-person-fill"></i> Minha Conta
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>
    
    <div class="container my-5">
        <div id="mensagem-container"></div>

        <!-- Seção de Cadastro -->
        <div class="section-header">
            <h4><i class="bi bi-plus-circle"></i> Cadastrar Novo Produto</h4>
        </div>
        <div class="card p-4 shadow-sm mb-5" id="form-cadastro-container">
            <!-- O formulário será carregado via JS -->
        </div>

        <!-- Seção de Filtros -->
        <div class="section-header">
            <h4><i class="bi bi-filter"></i> Filtros</h4>
        </div>
        <div class="card p-4 shadow-sm mb-4" id="filtros-container">
            <!-- Os filtros serão carregados via JS -->
        </div>

        <!-- Seção de Listagem -->
        <div class="section-header">
            <h4><i class="bi bi-list-ul"></i> Produtos Cadastrados</h4>
        </div>
        <div class="card p-4 shadow-sm">
            <div id="produtos-container" class="table-responsive">
                <!-- Tabela será carregada via JS -->
            </div>
        </div>
    </div>

    <!-- Modal de Edição -->
    <div class="modal fade" id="modalEditar" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title"><i class="bi bi-pencil"></i> Editar Produto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="modal-editar-container">
                    <!-- Formulário de edição será carregado via JS -->
                </div>
            </div>
        </div>
    </div>

     <!-- ====================== FOOTER ====================== -->
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
    <script src="public/js/admin.js"></script>

  <script>
    console.log('🔍 VERIFICAÇÃO DE INICIALIZAÇÃO:');
    
    // Verificar se o script admin.js foi carregado
    console.log('📜 AdminApp definido?', typeof AdminApp);
    
    // Forçar inicialização se necessário
    if (typeof AdminApp === 'function') {
        console.log('✅ AdminApp está disponível como função');
        
        // Pequeno delay para garantir que o DOM está pronto
        setTimeout(() => {
            console.log('🚀 FORÇANDO INICIALIZAÇÃO DO ADMINAPP');
            window.adminApp = new AdminApp();
        }, 100);
        
    } else {
        console.error('❌ AdminApp NÃO está definido!');
        console.error('Possíveis problemas:');
        console.error('1. Arquivo admin.js não foi carregado');
        console.error('2. Erro de sintaxe no admin.js');
        console.error('3. Caminho do arquivo incorreto');
        
        // Mostrar erro na tela
        document.getElementById('mensagem-container').innerHTML = `
            <div class="alert alert-danger">
                <h5><i class="bi bi-exclamation-triangle"></i> Erro Crítico</h5>
                <p>O sistema de administração não foi carregado.</p>
                <p>Possíveis causas:</p>
                <ul>
                    <li>Arquivo JavaScript não encontrado</li>
                    <li>Erro de sintaxe no código</li>
                    <li>Problema de permissões</li>
                </ul>
                <button class="btn btn-warning mt-2" onclick="location.reload()">
                    <i class="bi bi-arrow-clockwise"></i> Recarregar Página
                </button>
            </div>
        `;
    }
</script>
     
</body>
</html>