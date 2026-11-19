<?php
require_once __DIR__ . '/src/Database.php';

$pdo = Database::getConnection();
$mensagem = "";

// 1. Processa a alteração de preço e quantidade (aciona o TRIGGER no banco)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'atualizar_livro') {
    $id_livro = (int)$_POST['id_livro'];
    $novo_preco = (float)str_replace(',', '.', $_POST['novo_preco']);
    $nova_quantidade = (int)$_POST['nova_quantidade'];

    if ($id_livro > 0 && $novo_preco >= 0 && $nova_quantidade >= 0) {
        $stmt = $pdo->prepare("UPDATE livro SET preco = :preco, quantidade = :quantidade WHERE id = :id");
        $stmt->execute([
            ':preco' => $novo_preco,
            ':quantidade' => $nova_quantidade,
            ':id' => $id_livro
        ]);
        $mensagem = "Dados atualizados com sucesso! A auditoria registrou as alterações.";
    }
}

// 2. Busca lista de bibliotecas para os checkboxes
$bibliotecas = $pdo->query("SELECT id, nome FROM biblioteca")->fetchAll();

// 3. Busca todo o histórico de auditoria (preço e quantidade)
$auditorias_raw = $pdo->query("
    SELECT 
        id_livro, 
        preco_antigo, 
        preco_novo, 
        quantidade_antiga, 
        quantidade_nova, 
        DATE_FORMAT(data_alteracao, '%d/%m/%Y %H:%i:%s') as data_formatada 
    FROM auditoria_livro 
    ORDER BY data_alteracao DESC, id DESC
")->fetchAll();

$historico_por_livro = [];
foreach ($auditorias_raw as $reg) {
    $historico_por_livro[$reg['id_livro']][] = $reg;
}

// 4. Captura filtros da estante
$metodo = $_GET['metodo'] ?? 'view';
// Se acabou de mudar para procedure e não marcou nada ainda, marca todas por padrão
$bibliotecas_selecionadas = $_GET['bibliotecas'] ?? ($metodo === 'procedure' && !isset($_GET['filtro_enviado']) ? array_column($bibliotecas, 'id') : []);
$preco_max = isset($_GET['preco_max']) ? (float)$_GET['preco_max'] : 150.00;
$termo_busca = trim($_GET['termo'] ?? '');
$apenas_estoque = isset($_GET['apenas_estoque']) ? 1 : 0;

$livros = [];
$metodo_utilizado = "";

if ($metodo === 'procedure') {
    $metodo_utilizado = "PROCEDURE Interativa (SQBuscarBiblioteca)";
    
    if (!empty($bibliotecas_selecionadas) && is_array($bibliotecas_selecionadas)) {
        $stmt = $pdo->prepare("CALL SQBuscarBiblioteca(:id_bib, :preco_max, :termo, :apenas_estoque)");
        
        foreach ($bibliotecas_selecionadas as $id_bib) {
            $id_int = (int)$id_bib;
            $stmt->bindValue(':id_bib', $id_int, PDO::PARAM_INT);
            $stmt->bindValue(':preco_max', $preco_max);
            $stmt->bindValue(':termo', $termo_busca, PDO::PARAM_STR);
            $stmt->bindValue(':apenas_estoque', $apenas_estoque, PDO::PARAM_INT);
            $stmt->execute();
            
            $resultados = $stmt->fetchAll();
            $livros = array_merge($livros, $resultados);
            $stmt->closeCursor();
        }
    }
} else {
    $stmt = $pdo->query("SELECT * FROM QLivros");
    $livros = $stmt->fetchAll();
    $metodo_utilizado = "VIEW (QLivros)";
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estante 2D - Biblioteca</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container">
    <?php if ($mensagem): ?>
        <div class="alerta-sucesso"><?= htmlspecialchars($mensagem) ?></div>
    <?php endif; ?>

    <div class="controles">
        <div class="topo-controles">
            <strong>Filtro do Acervo</strong>
            <div>
                Ativo: <span class="badge"><?= $metodo_utilizado ?></span>
            </div>
        </div>

        <form method="GET" action="" id="form-filtro" class="form-grupo">
            <input type="hidden" name="filtro_enviado" value="1">

            <!-- Dropdown Principal -->
            <div class="linha-campo">
                <label for="metodo"><strong>Método de Busca:</strong></label>
                <select name="metodo" id="metodo" onchange="alternarModoBusca()">
                    <option value="view" <?= ($metodo === 'view') ? 'selected' : '' ?>>View (Acervo Completo)</option>
                    <option value="procedure" <?= ($metodo === 'procedure') ? 'selected' : '' ?>>Procedure (Busca Interativa)</option>
                </select>
            </div>

            <!-- Painel Interativo da Procedure -->
            <div id="painel-procedure" class="painel-procedure <?= ($metodo === 'procedure') ? 'ativo' : '' ?>">
                
                <!-- 1. Checkboxes das Bibliotecas -->
                <div class="bloco-filtro">
                    <strong>1. Bibliotecas:</strong>
                    <div class="checkbox-lista">
                        <?php foreach ($bibliotecas as $bib): ?>
                            <label class="checkbox-item">
                                <input 
                                    type="checkbox" 
                                    name="bibliotecas[]" 
                                    value="<?= $bib['id'] ?>"
                                    onchange="enviarFiltroAuto()"
                                    <?= in_array($bib['id'], $bibliotecas_selecionadas) ? 'checked' : '' ?>
                                >
                                <?= htmlspecialchars($bib['nome']) ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- 2. Slider de Preço Máximo -->
                <div class="bloco-filtro">
                    <div class="slider-header">
                        <label for="preco_max"><strong>2. Preço Máximo:</strong></label>
                        <span id="valor-slider" class="valor-destaque">
                            R$ <?= number_format($preco_max, 2, ',', '.') ?>
                        </span>
                    </div>
                    <input 
                        type="range" 
                        name="preco_max" 
                        id="preco_max" 
                        min="10" 
                        max="150" 
                        step="5" 
                        value="<?= $preco_max ?>"
                        oninput="atualizarTextoSlider(this.value)"
                        onchange="enviarFiltroAuto()"
                    >
                </div>

                <!-- 3. Busca por Título/Autor + Apenas em Estoque -->
                <div class="bloco-filtro-duplo">
                    <div class="campo-texto">
                        <label for="termo"><strong>3. Título ou Autor:</strong></label>
                        <input 
                            type="text" 
                            name="termo" 
                            id="termo" 
                            placeholder="Ex: Machado, 1984..." 
                            value="<?= htmlspecialchars($termo_busca) ?>"
                        >
                    </div>

                    <div class="campo-estoque">
                        <label class="checkbox-item">
                            <input 
                                type="checkbox" 
                                name="apenas_estoque" 
                                value="1"
                                onchange="enviarFiltroAuto()"
                                <?= $apenas_estoque ? 'checked' : '' ?>
                            >
                            <strong>Apenas livros em estoque (&gt; 0)</strong>
                        </label>
                    </div>
                </div>

                <div class="linha-acoes-procedure">
                    <button type="submit">Aplicar Pesquisa</button>
                    <a href="?metodo=procedure" class="btn-limpar">Limpar Filtros</a>
                </div>
            </div>
        </form>
    </div>

        <p class="dica-clique">💡 <em>Clique sobre qualquer livro na estante para editar preço, estoque ou ver o histórico de auditoria.</em></p>

    <div class="estante">
        <?php 
        $prateleiras = array_chunk($livros, 12);
        if (empty($prateleiras)): 
        ?>
            <div class="prateleira">
                <div class="vazio">
                    <?= ($metodo === 'procedure' && empty($bibliotecas_selecionadas)) 
                        ? 'Marque pelo menos uma biblioteca acima para listar os livros.' 
                        : 'Nenhum livro encontrado com os filtros selecionados.' ?>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($prateleiras as $livros_prateleira): ?>
                <div class="prateleira">
                    <?php foreach ($livros_prateleira as $livro): ?>
                        <?php 
                            $preco_unitario = (float)$livro['preco'];
                            $quantidade_estoque = (int)$livro['quantidade'];
                            $valor_total = $preco_unitario * $quantidade_estoque;
                        ?>
                        <div 
                            class="livro <?= ($quantidade_estoque === 0) ? 'sem-estoque' : '' ?>" 
                            style="background-color: <?= htmlspecialchars($livro['cor_capa']) ?>;"
                            onclick="abrirModalLivro(
                                <?= $livro['livro_id'] ?>, 
                                '<?= htmlspecialchars(addslashes($livro['titulo'])) ?>', 
                                '<?= htmlspecialchars(addslashes($livro['autor'])) ?>', 
                                '<?= number_format($preco_unitario, 2, '.', '') ?>',
                                <?= $quantidade_estoque ?>
                            )"
                        >
                            <span class="livro-titulo"><?= htmlspecialchars($livro['titulo']) ?></span>
                            
                            <div class="info-tooltip">
                                <strong><?= htmlspecialchars($livro['titulo']) ?></strong><br>
                                Autor: <?= htmlspecialchars($livro['autor']) ?><br>
                                Local: <?= htmlspecialchars($livro['biblioteca']) ?><br>
                                Preço Unit.: R$ <?= number_format($preco_unitario, 2, ',', '.') ?><br>
                                Estoque: <?= $quantidade_estoque ?> un.<br>
                                <hr class="tooltip-divisor">
                                <span class="tooltip-total">Total: R$ <?= number_format($valor_total, 2, ',', '.') ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- MODAL (Card de Edição e Histórico) -->
<div id="modal-livro" class="modal-overlay" onclick="fecharModalFora(event)">
    <div class="modal-card">
        <div class="modal-header">
            <h3 id="modal-titulo">Título do Livro</h3>
            <button type="button" class="btn-fechar" onclick="fecharModal()">&times;</button>
        </div>

        <p class="modal-autor">Autor: <span id="modal-autor-nome"></span></p>

        <form method="POST" action="" class="form-edicao">
            <input type="hidden" name="acao" value="atualizar_livro">
            <input type="hidden" name="id_livro" id="modal-id-livro">

            <div class="campos-duplos">
                <div class="campo-item">
                    <label for="modal-input-preco"><strong>Preço (R$):</strong></label>
                    <input type="number" step="0.01" min="0" name="novo_preco" id="modal-input-preco" required>
                </div>

                <div class="campo-item">
                    <label for="modal-input-quantidade"><strong>Quantidade:</strong></label>
                    <input type="number" step="1" min="0" name="nova_quantidade" id="modal-input-quantidade" required>
                </div>
            </div>

            <button type="submit" class="btn-salvar">Salvar Alterações</button>
        </form>

        <hr class="divisor">

        <div class="acoes-historico">
            <button type="button" class="btn-historico" onclick="alternarHistorico()">
                📋 Ver Histórico de Alterações
            </button>
        </div>

        <div id="area-historico" class="area-historico">
            <h4>Histórico de Auditoria (Preço e Estoque)</h4>
            <div class="tabela-responsiva">
                <table class="tabela-auditoria">
                    <thead>
                        <tr>
                            <th>Data/Hora</th>
                            <th>Preço (Antigo → Novo)</th>
                            <th>Qtd (Antiga → Nova)</th>
                        </tr>
                    </thead>
                    <tbody id="corpo-tabela-historico"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    const historicoAuditoria = <?= json_encode($historico_por_livro) ?>;
</script>
<script src="script.js"></script>

</body>
</html>
