<?php
// app/controller/admincontroller.php
class AdminController
{
    private $produtoDAO;

    public function __construct($conn)
    {
        $this->produtoDAO = new ProdutoDAO($conn);
    }

    public function listarProdutos($filtros = [])
    {
        try {
            // Converter filtros para o formato esperado pelo ProdutoDAO
            $filtrosDAO = [];

            // Incluir vendidos se especificado
            // if (isset($filtros['vendido']) && $filtros['vendido'] == '1') {
            //    $filtrosDAO['incluir_vendidos'] = true;
            //   }

            // Filtro por vendido específico
            if (isset($filtros['vendido']) && ($filtros['vendido'] === '0' || $filtros['vendido'] === '1')) {
                $filtrosDAO['vendido'] = (int)$filtros['vendido'];
            }

            // Filtro por categoria (nome para ID)
            if (!empty($filtros['categoria'])) {
                $categoriaId = $this->produtoDAO->getCategoriaIdPorNome($filtros['categoria']);
                if ($categoriaId) {
                    $filtrosDAO['categoria'] = $categoriaId;
                }
            }

            // Outros filtros
            if (!empty($filtros['estado'])) {
                $filtrosDAO['estado'] = $filtros['estado'];
            }

            if (!empty($filtros['tamanho'])) {
                $filtrosDAO['tamanho'] = $filtros['tamanho'];
            }

            if (!empty($filtros['marca'])) {
                $filtrosDAO['marca'] = $filtros['marca'];
            }

            // Filtro por nome
            if (!empty($filtros['nome'])) {
                $filtrosDAO['nome'] = $filtros['nome'];
            }

            // Buscar produtos
            $produtos = $this->produtoDAO->listar($filtrosDAO);

            // Formatar para o frontend
            $produtosFormatados = [];

            foreach ($produtos as $produto) {
                // Buscar categoria pelo ID
                $categorias = $this->produtoDAO->getCategorias();
                $nomeCategoria = '';

                foreach ($categorias as $cat) {
                    if ($cat['idCategoria'] == $produto->getIdCategoria()) {
                        $nomeCategoria = $cat['descricao'];
                        break;
                    }
                }

                $produtosFormatados[] = [
                    'id' => $produto->getIdProduto(),
                    'nome' => $produto->getNome(),
                    'marca' => $produto->getMarca(),
                    'tamanho' => $produto->getTamanho(),
                    'estado' => $produto->getEstado(),
                    'categoria' => $nomeCategoria,
                    'idCategoria' => $produto->getIdCategoria(),
                    'preco' => (float)$produto->getPreco(),
                    'imagem' => $produto->getImagem(),
                    'promocao' => (bool)$produto->isPromocao(),
                    'estoque' => (int)$produto->getEstoque(),
                    'descricao' => $produto->getDescricao(),
                    'vendido' => (bool)$produto->getVendido(),
                    'dataVenda' => $produto->getDataVenda()
                ];
            }

            echo json_encode([
                'success' => true,
                'data' => $produtosFormatados,
                'total' => count($produtosFormatados)
            ], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            error_log("Erro ao listar produtos: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Erro ao listar produtos: ' . $e->getMessage(),
                'debug' => ['filtros_recebidos' => $filtros]
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    public function cadastrarProduto($data)
    {
        try {
            error_log("========== CADASTRAR PRODUTO ==========");
            error_log("Dados recebidos: " . print_r($data, true));
            error_log("Dados FILES: " . print_r($_FILES, true));

            // DEBUG: Verificar todos os campos
            foreach ($data as $key => $value) {
                error_log("Campo [$key] = " . (is_array($value) ? print_r($value, true) : $value));
            }

            // Validar dados obrigatórios
            $required = ['nome', 'preco', 'categoria', 'estado'];
            $missing = [];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    $missing[] = $field;
                }
            }

            if (!empty($missing)) {
                throw new Exception("Campos obrigatórios faltando: " . implode(', ', $missing));
            }

            // Validar preço
            $preco = floatval(str_replace(',', '.', $data['preco']));
            if ($preco <= 0) {
                throw new Exception("Preço deve ser maior que zero. Recebido: " . $data['preco']);
            }

            // Converter categoria de nome para ID
            $categoriaNome = trim($data['categoria']);
            error_log("Buscando ID para categoria: " . $categoriaNome);
            $categoriaId = $this->produtoDAO->getCategoriaIdPorNome($categoriaNome);

            if (!$categoriaId) {
                // Listar categorias disponíveis para debug
                $categorias = $this->produtoDAO->getCategorias();
                error_log("Categorias disponíveis: " . print_r($categorias, true));
                throw new Exception("Categoria inválida: '" . $categoriaNome . "'. Categorias válidas: " .
                    implode(', ', array_column($categorias, 'descricao')));
            }

            error_log("Categoria ID encontrada: " . $categoriaId);

            // Processar estoque - CORREÇÃO AQUI
            $estoque = 1; // padrão peça única

            // Verificar se o campo 'tem_estoque' existe (do checkbox)
            if (isset($data['tem_estoque']) && $data['tem_estoque'] === 'true') {
                // Se marcou "tem estoque", usar o valor do campo 'estoque'
                if (isset($data['estoque']) && !empty($data['estoque'])) {
                    $estoque = intval($data['estoque']);
                    if ($estoque < 1) {
                        $estoque = 1;
                    }
                } else {
                    $estoque = 2; // valor padrão quando marca checkbox mas não especifica quantidade
                }
            } else {
                // Se não marcou checkbox, verificar se veio campo 'estoque' diretamente
                if (isset($data['estoque']) && !empty($data['estoque'])) {
                    $estoque = intval($data['estoque']);
                    if ($estoque < 1) {
                        $estoque = 1;
                    }
                }
            }

            error_log("Estoque definido como: " . $estoque);

            // Processar booleanos CORRETAMENTE
            $promocao = false;
            if (isset($data['promocao'])) {
                $promocaoVal = $data['promocao'];
                error_log("Valor promocao recebido: " . $promocaoVal . " (tipo: " . gettype($promocaoVal) . ")");

                $promocao = (
                    $promocaoVal === 'true' ||
                    $promocaoVal === '1' ||
                    $promocaoVal === 'on' ||
                    $promocaoVal === true ||
                    $promocaoVal === 'yes' ||
                    $promocaoVal === 'sim'
                );
            }

            $vendido = false;
            if (isset($data['vendido'])) {
                $vendidoVal = $data['vendido'];
                error_log("Valor vendido recebido: " . $vendidoVal . " (tipo: " . gettype($vendidoVal) . ")");

                $vendido = (
                    $vendidoVal === 'true' ||
                    $vendidoVal === '1' ||
                    $vendidoVal === 'on' ||
                    $vendidoVal === true ||
                    $vendidoVal === 'yes' ||
                    $vendidoVal === 'sim'
                );
            }

            error_log("Promoção: " . ($promocao ? 'SIM' : 'NÃO'));
            error_log("Vendido: " . ($vendido ? 'SIM' : 'NÃO'));

            // UPLOAD DE IMAGEM
            $imagemNome = 'default.jpg';

            if (!empty($_FILES['imagem']) && isset($_FILES['imagem']['error']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
                error_log("Processando upload de imagem...");

                // Verificar se o diretório existe
                $uploadDir = __DIR__ . '/../../public/img/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                    error_log("Diretório criado: " . $uploadDir);
                }

                // Validar tipo de arquivo
                $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                $fileType = mime_content_type($_FILES['imagem']['tmp_name']);

                if (!in_array($fileType, $allowedTypes)) {
                    error_log("Tipo de arquivo não permitido: " . $fileType);
                    throw new Exception("Tipo de arquivo não permitido. Use JPG, PNG ou GIF.");
                }

                // Validar tamanho (5MB)
                if ($_FILES['imagem']['size'] > 5 * 1024 * 1024) {
                    throw new Exception("Imagem muito grande. Máximo 5MB.");
                }

                // Gerar nome único
                $extension = pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION);
                $imagemNome = 'produto_' . time() . '_' . rand(1000, 9999) . '.' . strtolower($extension);

                // Mover arquivo
                $targetPath = $uploadDir . $imagemNome;

                if (!move_uploaded_file($_FILES['imagem']['tmp_name'], $targetPath)) {
                    error_log("Falha ao mover arquivo para: " . $targetPath);
                    error_log("Erro do upload: " . print_r(error_get_last(), true));
                    $imagemNome = 'default.jpg';
                } else {
                    error_log("✅ Imagem salva com sucesso: " . $imagemNome . " em " . $targetPath);
                }
            } else {
                $uploadError = $_FILES['imagem']['error'] ?? 'N/A';
                error_log("Nenhuma imagem enviada ou erro no upload. Código de erro: " . $uploadError);

                // Mensagens de erro específicas
                $errorMessages = [
                    UPLOAD_ERR_INI_SIZE => 'O arquivo excede o tamanho máximo permitido pelo servidor',
                    UPLOAD_ERR_FORM_SIZE => 'O arquivo excede o tamanho máximo permitido pelo formulário',
                    UPLOAD_ERR_PARTIAL => 'O upload do arquivo foi feito parcialmente',
                    UPLOAD_ERR_NO_FILE => 'Nenhum arquivo foi enviado',
                    UPLOAD_ERR_NO_TMP_DIR => 'Pasta temporária não encontrada',
                    UPLOAD_ERR_CANT_WRITE => 'Falha ao gravar o arquivo no disco',
                    UPLOAD_ERR_EXTENSION => 'Uma extensão do PHP interrompeu o upload'
                ];

                if (isset($errorMessages[$uploadError])) {
                    error_log("Mensagem de erro do upload: " . $errorMessages[$uploadError]);
                }
            }

            // Criar objeto Produto
            error_log("Criando objeto Produto...");
            $produto = new Produto(
                trim($data['nome']),
                isset($data['marca']) ? trim($data['marca']) : 'Sem Marca',
                isset($data['tamanho']) ? trim($data['tamanho']) : '',
                trim($data['estado']),
                $categoriaId,
                $preco,
                $imagemNome,
                $promocao,
                $estoque,
                isset($data['descricao']) ? trim($data['descricao']) : '',
                $vendido,
                null,
                null
            );

            error_log("Objeto Produto criado:");
            error_log("Nome: " . $produto->getNome());
            error_log("Preço: " . $produto->getPreco());
            error_log("Categoria ID: " . $produto->getIdCategoria());
            error_log("Estado: " . $produto->getEstado());
            error_log("Estoque: " . $produto->getEstoque());

            // Salvar no banco
            error_log("Salvando produto no banco...");
            $sucesso = $this->produtoDAO->salvar($produto);

            if ($sucesso) {
                error_log("✅ Produto cadastrado com ID: " . $produto->getIdProduto());

                echo json_encode([
                    'success' => true,
                    'message' => 'Produto cadastrado com sucesso!',
                    'id' => $produto->getIdProduto(),
                    'produto' => [
                        'id' => $produto->getIdProduto(),
                        'nome' => $produto->getNome(),
                        'imagem' => $produto->getImagem()
                    ]
                ], JSON_UNESCAPED_UNICODE);
            } else {
                error_log("❌ Falha ao salvar no banco de dados");
                throw new Exception("Erro ao salvar no banco de dados. Verifique os logs.");
            }
        } catch (Exception $e) {
            error_log("❌ ERRO ao cadastrar produto: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());

            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Erro ao cadastrar produto: ' . $e->getMessage(),
                'debug' => [
                    'dados_recebidos' => $data,
                    'files_recebidos' => $_FILES,
                    'required_fields' => ['nome', 'preco', 'categoria', 'estado']
                ]
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    public function atualizarProduto($data, $arquivoImagem = null)
    {
        try {
            error_log("========== ATUALIZAR PRODUTO ==========");
            error_log("📦 Dados recebidos: " . print_r($data, true));

            // DEBUG 1: Verificar parâmetro $arquivoImagem
            error_log("📸 === DEBUG 1: PARÂMETRO ARQUIVO IMAGEM ===");
            error_log("Tipo do parâmetro: " . gettype($arquivoImagem));

            if ($arquivoImagem === null) {
                error_log("❌ Parâmetro \$arquivoImagem é NULL");
            } elseif (is_array($arquivoImagem)) {
                error_log("✅ Parâmetro \$arquivoImagem é array com " . count($arquivoImagem) . " elementos");
            } else {
                error_log("⚠️ Parâmetro \$arquivoImagem é do tipo: " . gettype($arquivoImagem));
            }

            if (is_array($arquivoImagem)) {
                error_log("📄 Conteúdo do array:");
                foreach ($arquivoImagem as $key => $value) {
                    error_log("  [$key] => " . ($key === 'tmp_name' ? '(tmp file)' : $value));
                }

                // Verificar código de erro
                $errorCode = $arquivoImagem['error'] ?? -1;
                $errorMessages = [
                    UPLOAD_ERR_OK => 'OK - Nenhum erro',
                    UPLOAD_ERR_INI_SIZE => 'INI_SIZE - Tamanho maior que upload_max_filesize',
                    UPLOAD_ERR_FORM_SIZE => 'FORM_SIZE - Tamanho maior que MAX_FILE_SIZE',
                    UPLOAD_ERR_PARTIAL => 'PARTIAL - Upload parcial',
                    UPLOAD_ERR_NO_FILE => 'NO_FILE - Nenhum arquivo enviado',
                    UPLOAD_ERR_NO_TMP_DIR => 'NO_TMP_DIR - Pasta temporária não existe',
                    UPLOAD_ERR_CANT_WRITE => 'CANT_WRITE - Não pode escrever no disco',
                    UPLOAD_ERR_EXTENSION => 'EXTENSION - Extensão PHP parou o upload'
                ];

                error_log("🔧 Código de erro do upload: " . $errorCode . " - " .
                    ($errorMessages[$errorCode] ?? 'Erro desconhecido'));

                if ($errorCode === UPLOAD_ERR_OK) {
                    error_log("✅ Upload OK! Verificando arquivo temporário...");
                    if (isset($arquivoImagem['tmp_name']) && file_exists($arquivoImagem['tmp_name'])) {
                        error_log("✅ Arquivo temporário existe: " . $arquivoImagem['tmp_name']);
                        error_log("✅ Tamanho: " . filesize($arquivoImagem['tmp_name']) . " bytes");
                    } else {
                        error_log("❌ Arquivo temporário NÃO existe");
                    }
                }
            }

            // DEBUG 2: Verificar $_FILES global
            error_log("🌐 === DEBUG 2: \$_FILES GLOBAL ===");
            error_log("Número de arquivos em \$_FILES: " . count($_FILES));

            if (isset($_FILES['imagem'])) {
                error_log("✅ \$_FILES['imagem'] EXISTE");
                error_log("Conteúdo de \$_FILES['imagem']: " . print_r($_FILES['imagem'], true));

                $errorCodeGlobal = $_FILES['imagem']['error'] ?? -1;
                error_log("🔧 Erro em \$_FILES['imagem']: " . $errorCodeGlobal);

                // Se o parâmetro estiver vazio mas $_FILES tem dados, usar $_FILES
                if ((!$arquivoImagem || !is_array($arquivoImagem) || $arquivoImagem['error'] !== UPLOAD_ERR_OK)
                    && isset($_FILES['imagem']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK
                ) {
                    error_log("🔄 Usando \$_FILES['imagem'] em vez do parâmetro");
                    $arquivoImagem = $_FILES['imagem'];
                }
            } else {
                error_log("❌ \$_FILES['imagem'] NÃO existe");
                error_log("Todas as chaves em \$_FILES: " . implode(', ', array_keys($_FILES)));
            }

            // DEBUG 3: Verificar configurações do PHP
            error_log("⚙️ === DEBUG 3: CONFIGURAÇÕES DO PHP ===");
            error_log("upload_max_filesize: " . ini_get('upload_max_filesize'));
            error_log("post_max_size: " . ini_get('post_max_size'));
            error_log("memory_limit: " . ini_get('memory_limit'));

            if (empty($data['id'])) {
                throw new Exception("ID do produto não informado");
            }

            // Buscar produto existente
            $produtoExistente = $this->produtoDAO->buscarPorId($data['id']);
            if (!$produtoExistente) {
                throw new Exception("Produto não encontrado");
            }

            error_log("📊 Produto encontrado: ID " . $produtoExistente->getIdProduto() .
                " - " . $produtoExistente->getNome());

            // Atualizar campos básicos
            if (isset($data['nome'])) {
                $produtoExistente->setNome($data['nome']);
            }

            if (isset($data['marca'])) {
                $produtoExistente->setMarca($data['marca']);
            }

            if (isset($data['tamanho'])) {
                $produtoExistente->setTamanho($data['tamanho']);
            }

            if (isset($data['estado'])) {
                $produtoExistente->setEstado($data['estado']);
            }

            if (isset($data['preco'])) {
                $produtoExistente->setPreco(floatval($data['preco']));
            }

            // Processar estoque corretamente
            if (isset($data['estoque'])) {
                $estoque = intval($data['estoque']);
                // Se tem o campo 'tem_estoque' e é false, e estoque está vazio, considerar 1
                if (isset($data['tem_estoque']) && $data['tem_estoque'] === 'false' && empty($data['estoque'])) {
                    $estoque = 1;
                }
                $produtoExistente->setEstoque($estoque);
                error_log("📦 Estoque definido para: " . $estoque);
            }

            if (isset($data['descricao'])) {
                $produtoExistente->setDescricao($data['descricao']);
            }

            // Processar booleanos corretamente
            $promocao = false;
            if (isset($data['promocao'])) {
                if (is_string($data['promocao'])) {
                    $promocao = $data['promocao'] === 'true' || $data['promocao'] === '1' || $data['promocao'] === 'on';
                } else {
                    $promocao = (bool)$data['promocao'];
                }
                error_log("🎯 Promoção: " . ($promocao ? 'SIM' : 'NÃO'));
            }
            $produtoExistente->setPromocao($promocao);

            $vendido = false;
            if (isset($data['vendido'])) {
                if (is_string($data['vendido'])) {
                    $vendido = $data['vendido'] === 'true' || $data['vendido'] === '1' || $data['vendido'] === 'on';
                } else {
                    $vendido = (bool)$data['vendido'];
                }
                error_log("💰 Vendido: " . ($vendido ? 'SIM' : 'NÃO'));
            }
            $produtoExistente->setVendido($vendido);

            // Atualizar categoria se fornecida
            if (!empty($data['categoria'])) {
                $categoriaId = $this->produtoDAO->getCategoriaIdPorNome($data['categoria']);
                if ($categoriaId) {
                    $produtoExistente->setIdCategoria($categoriaId);
                    error_log("📁 Categoria atualizada para ID: " . $categoriaId);
                }
            }

            // ========== PROCESSAMENTO DA IMAGEM ==========
            error_log("🖼️ === PROCESSAMENTO DA IMAGEM ===");
            error_log("Imagem atual do produto: " . $produtoExistente->getImagem());

            // Se tem arquivo de imagem enviado e válido
            if (is_array($arquivoImagem) && isset($arquivoImagem['error']) && $arquivoImagem['error'] === UPLOAD_ERR_OK) {
                error_log("✅ Arquivo de imagem recebido: " . $arquivoImagem['name']);

                // Diretório de upload
                $uploadDir = __DIR__ . '/../../public/img/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                // Validar tipo de arquivo
                $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                $fileType = mime_content_type($arquivoImagem['tmp_name']);

                if (!in_array($fileType, $allowedTypes)) {
                    error_log("❌ Tipo de arquivo não permitido: " . $fileType);
                    // Não lançar exceção, apenas logar e continuar
                } else if ($arquivoImagem['size'] > 5 * 1024 * 1024) {
                    error_log("❌ Imagem muito grande: " . $arquivoImagem['size'] . " bytes");
                    // Não lançar exceção, apenas logar e continuar
                } else {
                    // Remover imagem antiga se não for default.jpg
                    $imagemAtual = $produtoExistente->getImagem();
                    if ($imagemAtual && $imagemAtual !== 'default.jpg') {
                        $imagemAntigaPath = $uploadDir . $imagemAtual;
                        if (file_exists($imagemAntigaPath)) {
                            unlink($imagemAntigaPath);
                            error_log("🗑️ Imagem antiga removida: " . $imagemAtual);
                        }
                    }

                    // Gerar nome único para nova imagem
                    $extension = pathinfo($arquivoImagem['name'], PATHINFO_EXTENSION);
                    $novoNome = 'produto_' . time() . '_' . rand(1000, 9999) . '.' . strtolower($extension);
                    $destino = $uploadDir . $novoNome;

                    // Mover arquivo
                    if (move_uploaded_file($arquivoImagem['tmp_name'], $destino)) {
                        error_log("✅ Nova imagem salva: " . $novoNome);
                        $produtoExistente->setImagem($novoNome);
                    } else {
                        error_log("❌ Falha ao mover arquivo para: " . $destino);
                        error_log("Erro do upload: " . print_r(error_get_last(), true));
                    }
                }
            } else {
                // Se não enviou nova imagem, manter a atual
                $errorCode = $arquivoImagem['error'] ?? 'N/A';
                error_log("🔄 Nenhuma nova imagem enviada ou erro no upload. Código de erro: " . $errorCode);
                error_log("🖼️ Mantendo imagem atual: " . $produtoExistente->getImagem());
            }

            // ========== SALVAR NO BANCO ==========
            error_log("💾 === SALVANDO NO BANCO DE DADOS ===");
            $sucesso = $this->produtoDAO->atualizar($produtoExistente);

            if ($sucesso) {
                error_log("✅ Produto atualizado com sucesso no banco!");

                $response = [
                    'success' => true,
                    'message' => 'Produto atualizado com sucesso!',
                    'produto' => [
                        'id' => $produtoExistente->getIdProduto(),
                        'imagem' => $produtoExistente->getImagem(),
                        'nome' => $produtoExistente->getNome()
                    ]
                ];

                error_log("📤 Resposta enviada: " . print_r($response, true));

                echo json_encode($response, JSON_UNESCAPED_UNICODE);
            } else {
                error_log("❌ Falha ao salvar no banco de dados");
                throw new Exception("Erro ao salvar no banco de dados");
            }
        } catch (Exception $e) {
            error_log("❌ ===== ERRO AO ATUALIZAR PRODUTO =====");
            error_log("Mensagem: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());

            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Erro ao atualizar produto: ' . $e->getMessage(),
                'debug' => [
                    'dados_recebidos' => $data,
                    'arquivo_imagem_param' => $arquivoImagem,
                    'files_global' => $_FILES
                ]
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    // admincontroller.php - MÉTODO excluirProduto()
    public function excluirProduto($data)
    {
        try {
            error_log("========== EXCLUIR PRODUTO ==========");
            error_log("ID recebido: " . print_r($data, true));

            if (empty($data['id'])) {
                throw new Exception("ID do produto não informado");
            }

            // 1. Buscar o produto para obter a imagem
            $produto = $this->produtoDAO->buscarPorId($data['id']);

            if (!$produto) {
                throw new Exception("Produto não encontrado");
            }

            // 2. Remover a imagem física se não for default.jpg
            $imagem = $produto->getImagem();
            error_log("Imagem do produto: " . $imagem);

            if ($imagem && $imagem !== 'default.jpg') {
                $uploadDir = __DIR__ . '/../../public/img/';
                $imagemPath = $uploadDir . $imagem;

                if (file_exists($imagemPath)) {
                    if (unlink($imagemPath)) {
                        error_log("✅ Imagem removida: " . $imagemPath);
                    } else {
                        error_log("⚠️ Não foi possível remover a imagem: " . $imagemPath);
                    }
                }
            }

            // 3. Excluir do banco de dados
            error_log("Excluindo produto ID: " . $data['id'] . " do banco...");
            $sucesso = $this->produtoDAO->excluir($data['id']);

            if ($sucesso) {
                error_log("✅ Produto excluído com sucesso do banco!");

                echo json_encode([
                    'success' => true,
                    'message' => 'Produto excluído com sucesso!'
                ], JSON_UNESCAPED_UNICODE);
            } else {
                error_log("❌ Falha ao excluir do banco de dados");
                throw new Exception("Erro ao excluir produto do banco de dados");
            }
        } catch (Exception $e) {
            error_log("❌ ERRO ao excluir produto: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());

            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Erro ao excluir produto: ' . $e->getMessage(),
                'debug' => ['dados_recebidos' => $data]
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    public function marcarComoVendido($data)
    {
        try {
            error_log("========== MARCAR COMO VENDIDO ==========");
            error_log("ID recebido: " . print_r($data, true));

            if (empty($data['id'])) {
                throw new Exception("ID do produto não informado");
            }

            // Buscar produto atual
            $produto = $this->produtoDAO->buscarPorId($data['id']);
            if (!$produto) {
                throw new Exception("Produto não encontrado");
            }

            // Marcar como vendido (alternar status)
            $novoStatus = !$produto->getVendido(); // Alterna entre true/false
            $produto->setVendido($novoStatus);

            // Se está marcando como vendido, definir data_venda
            if ($novoStatus) {
                $produto->setDataVenda(date('Y-m-d H:i:s'));
            } else {
                $produto->setDataVenda(null); // Remover data se desmarcar
            }

            error_log("Atualizando produto ID: " . $data['id'] .
                " - Vendido: " . ($novoStatus ? 'SIM' : 'NÃO'));

            // Atualizar no banco
            $sucesso = $this->produtoDAO->atualizar($produto);

            if ($sucesso) {
                $mensagem = $novoStatus ?
                    '✅ Produto marcado como VENDIDO!' :
                    '✅ Produto marcado como DISPONÍVEL novamente!';

                error_log($mensagem);

                echo json_encode([
                    'success' => true,
                    'message' => $mensagem,
                    'vendido' => $novoStatus
                ], JSON_UNESCAPED_UNICODE);
            } else {
                error_log("❌ Falha ao atualizar status no banco");
                throw new Exception("Erro ao atualizar status do produto");
            }
        } catch (Exception $e) {
            error_log("❌ ERRO ao marcar como vendido: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());

            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Erro ao marcar como vendido: ' . $e->getMessage(),
                'debug' => ['dados_recebidos' => $data]
            ], JSON_UNESCAPED_UNICODE);
        }
    }
    public function listarCategorias()
    {
        try {
            $categorias = $this->produtoDAO->getCategorias();

            // Formatar para o frontend
            $categoriasFormatadas = array_map(function ($cat) {
                return [
                    'id' => $cat['idCategoria'],
                    'nome' => $cat['descricao']
                ];
            }, $categorias);

            echo json_encode([
                'success' => true,
                'data' => $categoriasFormatadas
            ], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            error_log("Erro ao listar categorias: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Erro ao listar categorias'
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    public function listarFiltros()
    {
        try {
            $filtros = [
                'categorias' => array_map(function ($cat) {
                    return ['id' => $cat['idCategoria'], 'nome' => $cat['descricao']];
                }, $this->produtoDAO->getCategorias()),
                'estados' => $this->produtoDAO->listarEstados(),
                'tamanhos' => $this->produtoDAO->listarTamanhos(),
                'marcas' => $this->produtoDAO->getOpcoesFiltro('marca')
            ];

            echo json_encode([
                'success' => true,
                'data' => $filtros
            ], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            error_log("Erro ao listar filtros: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Erro ao listar filtros'
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * Método auxiliar para processar upload de imagem
     * @param array $arquivoImagem Dados do arquivo de imagem
     * @param string $imagemAtual Nome da imagem atual (opcional)
     * @return string Nome da nova imagem ou imagem atual se não houver upload
     */
    private function processarUploadImagem($arquivoImagem, $imagemAtual = null)
    {
        if (!$arquivoImagem || $arquivoImagem['error'] !== UPLOAD_ERR_OK) {
            return $imagemAtual;
        }

        require_once __DIR__ . '/../model/upload_imagem.php';
        $uploader = new UploadImagem();

        // O método upload() da classe UploadImagem já cuida de remover a imagem antiga
        // se você passar o $imagemAtual como segundo parâmetro
        $novaImagem = $uploader->upload($arquivoImagem, $imagemAtual);

        return $novaImagem ?: $imagemAtual;
    }

    /**
     * Método auxiliar para remover imagem do produto
     * @param string $nomeImagem Nome da imagem a ser removida
     * @return bool True se a imagem foi removida, false caso contrário
     */
    private function removerImagemProduto($nomeImagem)
    {
        if ($nomeImagem && $nomeImagem !== 'default.jpg') {
            require_once __DIR__ . '/../model/upload_imagem.php';
            $uploader = new UploadImagem();
            return $uploader->removerImagem($nomeImagem);
        }
        return false;
    }
}
