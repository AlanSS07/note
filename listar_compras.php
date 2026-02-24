<?php 
require_once 'config.php';
include 'session.php'; 

$email_logado = $_SESSION['user_email'];

// Captura e padroniza os filtros do GET para bater com o banco (MAIÚSCULO)
$filtro_produto = mb_strtoupper($_GET['f_produto'] ?? '', 'UTF-8');
$filtro_marca   = mb_strtoupper($_GET['f_marca'] ?? '', 'UTF-8');
$filtro_modelo  = mb_strtoupper($_GET['f_modelo'] ?? '', 'UTF-8');
$filtro_valor   = $_GET['f_valor'] ?? '';
$filtro_status  = $_GET['f_status'] ?? ''; 
$data_inicio    = $_GET['d_inicio'] ?? '';
$data_fim       = $_GET['d_fim'] ?? '';

echo "<h2>Lista de Gastos:</h2>";
?>

<fieldset style="border-radius: 8px; background: #f9f9f9; border: 1px solid #ccc;">
    <legend><strong>Filtros de Busca</strong></legend>
    <form method="GET">
        <input type="text" name="f_produto" placeholder="Produto / Serviço" value="<?php echo htmlspecialchars($filtro_produto); ?>">
        <input type="text" name="f_marca" placeholder="Marca / Empresa" value="<?php echo htmlspecialchars($filtro_marca); ?>">
        <input type="text" name="f_modelo" placeholder="Modelo / Descrição" value="<?php echo htmlspecialchars($filtro_modelo); ?>">
        
        <input type="number" name="f_valor" step="0.01" placeholder="Valor até R$" value="<?php echo htmlspecialchars($filtro_valor); ?>">

        <select name="f_status">
            <option value="">Status (Todos)</option>
            <option value="1" <?php echo ($filtro_status === '1') ? 'selected' : ''; ?>>Concluído</option>
            <option value="0" <?php echo ($filtro_status === '0') ? 'selected' : ''; ?>>Pendente</option>
        </select>
        
        <br><br>
        <label>Período:</label>
        <input type="date" name="d_inicio" value="<?php echo htmlspecialchars($data_inicio); ?>">
        a
        <input type="date" name="d_fim" value="<?php echo htmlspecialchars($data_fim); ?>">

        <button type="submit" style="cursor:pointer">Filtrar</button>
        <a href="listar_compras.php">Limpar</a>
    </form>
</fieldset>
<br>

<?php
try {
    // Consulta baseada no usuário logado
    $sql = "SELECT * FROM compras WHERE usuario_email = :email"; 
    $params = [':email' => $email_logado];

    // Aplicação dinâmica dos filtros
    if ($filtro_produto) {
        $sql .= " AND produto LIKE :produto";
        $params[':produto'] = "%$filtro_produto%";
    }
    if ($filtro_marca) {
        $sql .= " AND marca LIKE :marca";
        $params[':marca'] = "%$filtro_marca%";
    }
    if ($filtro_modelo) {
        $sql .= " AND modelo LIKE :modelo";
        $params[':modelo'] = "%$filtro_modelo%";
    }
    if ($filtro_valor !== '') {
        $sql .= " AND valor <= :valor";
        $params[':valor'] = $filtro_valor;
    }
    if ($filtro_status !== '') {
        $sql .= " AND concluido = :status";
        $params[':status'] = $filtro_status;
    }

    // Filtro de data inteligente (Vencimento para pendentes, Conclusão para pagos)
    if ($data_inicio !== '') {
        $sql .= " AND ((concluido = 1 AND data_conclusao >= :d_ini) OR (concluido = 0 AND data_vencimento >= :d_ini))";
        $params[':d_ini'] = $data_inicio;
    }
    if ($data_fim !== '') {
        $sql .= " AND ((concluido = 1 AND data_conclusao <= :d_fim) OR (concluido = 0 AND data_vencimento <= :d_fim))";
        $params[':d_fim'] = $data_fim;
    }

    $sql .= " ORDER BY concluido ASC, data_vencimento ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $contador = 0;
    $total_realizado = 0;
    $total_pendente = 0;

    echo "<ul style='list-style: none; padding: 0;'>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $contador++; 
        $subtotal = $row['valor'] * $row['quantidade'];
        
        $txt_parcela = ($row['parcelas_total'] > 1) ? " <small style='color:#666;'>({$row['parcela_atual']}/{$row['parcelas_total']})</small>" : "";

        if ($row['concluido']) {
            $total_realizado += $subtotal;
            $status_html = '<span style="color: green; font-weight: bold;">[PAGO]</span>';
            $data_exib = $row['data_conclusao'] ? date('d/m/Y', strtotime($row['data_conclusao'])) : '---';
            $info_data = " | <small>Pago em: $data_exib</small>";
        } else {
            $total_pendente += $subtotal;
            $status_html = '<span style="color: red; font-weight: bold;">[PENDENTE]</span>';
            $data_exib = $row['data_vencimento'] ? date('d/m/Y', strtotime($row['data_vencimento'])) : '---';
            $info_data = " | <small>Vence em: $data_exib</small>";
        }

        echo "<li style='border-bottom: 1px solid #eee; padding: 10px 0;'>";
        echo "<strong>" . htmlspecialchars($row['produto']) . "</strong>" . $txt_parcela . "<br>";
        echo "<small>Marca / Empresa: " . htmlspecialchars($row['marca'] ?: '---') . " | Modelo / Descrição: " . htmlspecialchars($row['modelo'] ?: '---') . "</small><br>";
        echo "Valor: R$ " . number_format($row['valor'], 2, ',', '.') . " (x" . $row['quantidade'] . ") = <strong>R$ " . number_format($subtotal, 2, ',', '.') . "</strong> ";
        echo $status_html . $info_data;

        echo "<br><div style='margin-top:5px;'>";
        echo "<a href='editar_compras.php?id=" . $row['id_compra'] . "'>Editar</a> | ";
        
        if ($row['concluido']) {
            echo "<a href='marcar_concluido.php?tipo=compras&id=" . $row['id_compra'] . "&action=desmarcar' style='color:orange;'>Marcar Pendente</a>";
        } else {
            echo "<a href='marcar_concluido.php?tipo=compras&id=" . $row['id_compra'] . "&action=marcar' style='color:blue;'>Marcar como Pago</a>";
        }

        echo " | <a href='deletar.php?tipo=compras&id=" . $row['id_compra'] . "' 
        style='color:red;' onclick=\"return confirmarDelecao(event, '" . htmlspecialchars($row['produto']) . "')\">Deletar</a>";
        echo "</div></li>";
    }
    echo "</ul>";

    // Painel de Resumo
    echo "<div style='margin-top: 20px; padding: 15px; background-color: #f0f4f8; border: 1px solid #d1d9e0; border-radius: 8px;'>";
    echo "<strong>Resumo da Busca:</strong><br>";
    echo "Itens encontrados: " . $contador . "<br>";
    echo "Total Pago: <span style='color: green; font-weight:bold;'>R$ " . number_format($total_realizado, 2, ',', '.') . "</span><br>";
    echo "Total Pendente: <span style='color: red; font-weight:bold;'>R$ " . number_format($total_pendente, 2, ',', '.') . "</span>";
    echo "</div>";

} catch (PDOException $e) {
    echo "<p style='color:red;'>Erro ao carregar lista: " . $e->getMessage() . "</p>";
}
?>

<script>
function confirmarDelecao(event, produto) {
    return confirm("Deseja realmente excluir '" + produto + "'?");
}
</script>

<p>
    <a href="adicionar_compra.php"><b>+ Nova Compra</b></a> | 
    <a href="index.php">Voltar ao Início</a>
</p>