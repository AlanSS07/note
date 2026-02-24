
<?php
require_once 'config.php';
include 'session.php'; // Já verifica login e inicia sessão

$email_logado = $_SESSION['user_email'];

// Processar registro de venda
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao_venda'])) {
    $id_estoque = (int)$_POST['id_estoque'];
    $quantidade_vendida = (int)$_POST['quantidade_vendida'];
    $data_venda = $_POST['data_venda'];
    $usuario_email = $email_logado;

    // Buscar quantidade disponível
    $stmt = $pdo->prepare("SELECT quantidade FROM estoque WHERE id_estoque = :id AND usuario_email = :email");
    $stmt->execute([':id' => $id_estoque, ':email' => $usuario_email]);
    $qtd_total = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT SUM(quantidade_vendida) FROM vendas_estoque WHERE id_estoque = :id");
    $stmt->execute([':id' => $id_estoque]);
    $total_vendido = (int)$stmt->fetchColumn();
    $restante = $qtd_total - $total_vendido;

    if ($quantidade_vendida > 0 && $quantidade_vendida <= $restante) {
        // Registrar venda
        $stmt = $pdo->prepare("INSERT INTO vendas_estoque (id_estoque, quantidade_vendida, data_venda, usuario_email) VALUES (:id_estoque, :quantidade_vendida, :data_venda, :usuario_email)");
        $stmt->execute([
            ':id_estoque' => $id_estoque,
            ':quantidade_vendida' => $quantidade_vendida,
            ':data_venda' => $data_venda,
            ':usuario_email' => $usuario_email
        ]);
        // Atualizar campo vendido (opcional, pode ser só consulta dinâmica)
        $stmt = $pdo->prepare("UPDATE estoque SET vendido = vendido + :qtd WHERE id_estoque = :id");
        $stmt->execute([':qtd' => $quantidade_vendida, ':id' => $id_estoque]);
        echo "<script>alert('Venda registrada com sucesso!');window.location='listar_estoque.php';</script>";
        exit;
    } else {
        echo "<script>alert('Quantidade inválida para venda!');</script>";
    }
}

echo "<h2>Lista de Estoque:</h2>";

// Captura e padroniza filtros
$filtro_produto    = mb_strtoupper($_GET['f_produto'] ?? '', 'UTF-8');
$filtro_marca      = mb_strtoupper($_GET['f_marca'] ?? '', 'UTF-8');
$filtro_modelo     = mb_strtoupper($_GET['f_modelo'] ?? '', 'UTF-8');
$filtro_local      = $_GET['f_localizacao'] ?? ''; 
$filtro_valor      = $_GET['f_valor'] ?? '';
$filtro_status     = $_GET['f_status'] ?? ''; 

// Busca valores únicos APENAS do usuário logado para as sugestões (datalists)
$stmt_p = $pdo->prepare("SELECT DISTINCT produto FROM estoque WHERE usuario_email = :email ORDER BY produto");
$stmt_p->execute([':email' => $email_logado]);
$produtos_existentes = $stmt_p->fetchAll(PDO::FETCH_COLUMN);

$stmt_m = $pdo->prepare("SELECT DISTINCT marca FROM estoque WHERE usuario_email = :email ORDER BY marca");
$stmt_m->execute([':email' => $email_logado]);
$marcas_existentes = $stmt_m->fetchAll(PDO::FETCH_COLUMN);

$stmt_mod = $pdo->prepare("SELECT DISTINCT modelo FROM estoque WHERE usuario_email = :email ORDER BY modelo");
$stmt_mod->execute([':email' => $email_logado]);
$modelos_existentes = $stmt_mod->fetchAll(PDO::FETCH_COLUMN);

// Lógica de Localização personalizada para o usuário
$locais_padrao = [];
for ($i = 1; $i <= 20; $i++) { $locais_padrao[] = "Prateleira $i"; $locais_padrao[] = "Caixa $i"; }

$stmt_l = $pdo->prepare("SELECT DISTINCT prateleira FROM estoque WHERE usuario_email = :email AND prateleira != '' AND prateleira != '0' 
                          UNION 
                          SELECT DISTINCT caixa FROM estoque WHERE usuario_email = :email AND caixa != '' AND caixa != '0'");
$stmt_l->execute([':email' => $email_logado]);
$locais_bd = $stmt_l->fetchAll(PDO::FETCH_COLUMN);

$todos_locais = array_unique(array_merge($locais_padrao, $locais_bd));
sort($todos_locais);
?>

<fieldset style="border-radius: 8px; background: #f9f9f9; border: 1px solid #ccc;">
    <legend><strong>Filtros de Busca</strong></legend>
    <form method="GET">
        <input type="text" name="f_produto" placeholder="Produto" list="list_produtos" value="<?php echo htmlspecialchars($filtro_produto); ?>">
        <datalist id="list_produtos">
            <?php foreach($produtos_existentes as $p) echo "<option value='".htmlspecialchars($p)."'>"; ?>
        </datalist>

        <input type="text" name="f_marca" placeholder="Marca" list="list_marcas" value="<?php echo htmlspecialchars($filtro_marca); ?>">
        <datalist id="list_marcas">
            <?php foreach($marcas_existentes as $m) echo "<option value='".htmlspecialchars($m)."'>"; ?>
        </datalist>

        <input type="text" name="f_modelo" placeholder="Modelo" list="list_modelos" value="<?php echo htmlspecialchars($filtro_modelo); ?>">
        <datalist id="list_modelos">
            <?php foreach($modelos_existentes as $mod) echo "<option value='".htmlspecialchars($mod)."'>"; ?>
        </datalist>
        
        <input type="number" name="f_valor" step="0.01" placeholder="Valor até R$" value="<?php echo htmlspecialchars($filtro_valor); ?>">

        <select name="f_status">
            <option value="">Status (Todos)</option>
            <option value="0" <?php echo ($filtro_status === '0') ? 'selected' : ''; ?>>Em Estoque</option>
            <option value="1" <?php echo ($filtro_status === '1') ? 'selected' : ''; ?>>Vendido</option>
        </select>

        <br><br>
        
        <label>Localização:</label><br>
        <input type="text" name="f_prateleira" placeholder="Prateleira" value="<?php echo htmlspecialchars($_GET['f_prateleira'] ?? ''); ?>" style="width:110px;">
        <input type="text" name="f_caixa" placeholder="Caixa" value="<?php echo htmlspecialchars($_GET['f_caixa'] ?? ''); ?>" style="width:90px;">
        <input type="text" name="f_gaveta" placeholder="Gaveta" value="<?php echo htmlspecialchars($_GET['f_gaveta'] ?? ''); ?>" style="width:90px;">

        <button type="submit" style="cursor:pointer">Filtrar</button>
        <a href="listar_estoque.php">Limpar</a>
    </form>
</fieldset>
<br>

<?php
try {
    // Consulta restringindo ao usuário logado
    $sql = "SELECT * FROM estoque WHERE usuario_email = :email";
    $params = [':email' => $email_logado];

    if ($filtro_produto) { $sql .= " AND produto LIKE :produto"; $params[':produto'] = "%$filtro_produto%"; }
    if ($filtro_marca) { $sql .= " AND marca LIKE :marca"; $params[':marca'] = "%$filtro_marca%"; }
    if ($filtro_modelo) { $sql .= " AND modelo LIKE :modelo"; $params[':modelo'] = "%$filtro_modelo%"; }
    if ($filtro_valor !== '') { $sql .= " AND valor <= :valor"; $params[':valor'] = $filtro_valor; }
    if ($filtro_status !== '') { $sql .= " AND vendido = :status"; $params[':status'] = $filtro_status; }
    if (!empty($_GET['f_prateleira'])) {
        $sql .= " AND prateleira LIKE :prateleira";
        $params[':prateleira'] = '%' . $_GET['f_prateleira'] . '%';
    }
    if (!empty($_GET['f_caixa'])) {
        $sql .= " AND caixa LIKE :caixa";
        $params[':caixa'] = '%' . $_GET['f_caixa'] . '%';
    }
    if (!empty($_GET['f_gaveta'])) {
        $sql .= " AND gaveta LIKE :gaveta";
        $params[':gaveta'] = '%' . $_GET['f_gaveta'] . '%';
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $contador = 0;
    $valor_passivo_total = 0;
    $saldo_total_vendas = 0;

    echo "<ul style='list-style: none; padding: 0;'>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $contador++;
        // Buscar total vendido para este produto
        $stmt_v = $pdo->prepare("SELECT SUM(quantidade_vendida) FROM vendas_estoque WHERE id_estoque = :id");
        $stmt_v->execute([':id' => $row['id_estoque']]);
        $total_vendido = (int) $stmt_v->fetchColumn();
        $restante = $row['quantidade'] - $total_vendido;

        $valor_vendido = $row['valor'] * $total_vendido;
        $valor_restante = $row['valor'] * $restante;
        $saldo_total_vendas += $valor_vendido;
        $valor_passivo_total += $valor_restante;

        $status_style = ($restante <= 0) ? "color: green; font-weight: bold;" : "color: blue; font-weight: bold;";
        $status_label = ($restante <= 0) ? "[VENDIDO]" : "[EM ESTOQUE]";

        // Localização composta
        $loc_parts = [];
        if (!empty($row['prateleira'])) $loc_parts[] = $row['prateleira'];
        if (!empty($row['caixa'])) $loc_parts[] = $row['caixa'];
        if (!empty($row['gaveta'])) $loc_parts[] = $row['gaveta'];
        $loc_exibicao = count($loc_parts) ? implode(' | ', $loc_parts) : "Não definida";

        echo "<li style='border-bottom: 1px solid #eee; padding: 10px 0;'>";
        echo "<span style='$status_style'>$status_label</span> <strong>" . htmlspecialchars($row['produto']) . "</strong><br>";
        echo "<small>MARCA: " . htmlspecialchars($row['marca']) . " | MODELO: " . htmlspecialchars($row['modelo']) . " | LOCAL: " . htmlspecialchars($loc_exibicao) . "</small><br>";
        echo "Valor: R$ " . number_format($row['valor'], 2, ',', '.') . " (x" . $row['quantidade'] . ") | Subtotal: <strong>R$ " . number_format($row['valor'] * $row['quantidade'], 2, ',', '.') . "</strong>";
        echo "<br>Vendido: <strong>$total_vendido</strong> | Restante: <strong>$restante</strong>";

        // Listar vendas individuais para extorno
        $stmt_vendas = $pdo->prepare("SELECT id, quantidade_vendida, data_venda FROM vendas_estoque WHERE id_estoque = :id_estoque AND usuario_email = :email ORDER BY data_venda DESC");
        $stmt_vendas->execute([':id_estoque' => $row['id_estoque'], ':email' => $email_logado]);
        $vendas = $stmt_vendas->fetchAll(PDO::FETCH_ASSOC);
        if ($vendas && count($vendas) > 0) {
            echo "<ul style='margin: 8px 0 0 0; padding-left: 18px; font-size: 0.97em; color: #444;'>";
            foreach ($vendas as $venda) {
                echo "<li>";
                echo "Venda em <b>" . date('d/m/Y', strtotime($venda['data_venda'])) . "</b> - Quantidade: <b>" . $venda['quantidade_vendida'] . "</b> ";
                echo "<a href='deletar.php?tipo=venda&id=" . $venda['id'] . "' style='color:#e74c3c; margin-left:10px;' onclick=\"return confirm('Extornar esta venda? A quantidade será devolvida ao estoque.')\">Extornar</a>";
                echo "</li>";
            }
            echo "</ul>";
        }

        // Botão/formulário de venda
        if ($restante > 0) {
            echo "<br><button onclick=\"document.getElementById('form-venda-{$row['id_estoque']}').style.display='block'\" style='color:green;'>Vendi</button>";
            echo "<div id='form-venda-{$row['id_estoque']}' style='display:none; margin-top:8px;'>";
            echo "<form method='POST' style='display:inline;'>";
            echo "<input type='hidden' name='id_estoque' value='{$row['id_estoque']}'>";
            echo "<input type='number' name='quantidade_vendida' min='1' max='$restante' placeholder='Qtd' required style='width:60px;'>";
            echo "<input type='date' name='data_venda' value='".date('Y-m-d')."' required>";
            echo "<input type='hidden' name='acao_venda' value='1'>";
            echo "<button type='submit'>Registrar</button> ";
            echo "<button type='button' onclick=\"document.getElementById('form-venda-{$row['id_estoque']}').style.display='none'\">Cancelar</button>";
            echo "</form></div>";
        }

        echo " | <a href='editar_estoque.php?id=" . $row['id_estoque'] . "'>Editar</a> | ";
        echo "<a href='deletar.php?tipo=estoque&id=" . $row['id_estoque'] . "' style='color:red;' onclick=\"return confirmarDelecao(event, '" . htmlspecialchars($row['produto']) . "')\">Deletar</a>";
        echo "</li>";
    }
    echo "</ul>";

    // Resumo Financeiro do Estoque Filtrado
    echo "<div style='margin-top: 15px; padding: 15px; background-color: #f8f9fa; border: 1px solid #ddd; border-radius: 8px;'>";
    echo "<strong>Resumo Filtrado:</strong><br>";
    echo "Total de Itens: " . $contador . "<br>";
    echo "Patrimônio Parado: <span style='color: blue; font-weight:bold;'>R$ " . number_format($valor_passivo_total, 2, ',', '.') . "</span><br>";
    echo "Total em Vendas: <span style='color: green; font-weight:bold;'>R$ " . number_format($saldo_total_vendas, 2, ',', '.') . "</span>";
    echo "</div>";

} catch (PDOException $e) {
    echo "Erro: " . $e->getMessage();
}
?>

<script>
function confirmarDelecao(event, produto) {
    return confirm("Realmente deseja excluir '" + produto + "' do estoque?");
}
</script>

<p>
    <a href="adicionar_estoque.php"><b>+ Novo Item</b></a> | 
    <a href="index.php">Voltar ao Início</a>
</p>