<?php 
require_once 'config.php';
include 'session.php'; // Já faz session_start() e protege a página

$email_logado = $_SESSION['user_email'];

// Filtro de Período
$data_inicio = $_GET['d_inicio'] ?? date('Y-m-01'); 
$data_fim    = $_GET['d_fim']    ?? date('Y-m-t');  

try {
    // --- 1. ENTRADAS (Salário e Serviços) ---
    $sql_entradas = "SELECT SUM(valor) FROM financeiro WHERE usuario_email = :email AND data_entrada BETWEEN :inicio AND :fim";
    $stmt = $pdo->prepare($sql_entradas);
    $stmt->execute([':email' => $email_logado, ':inicio' => $data_inicio, ':fim' => $data_fim]);
    $total_entradas = $stmt->fetchColumn() ?: 0;

    $sql_lista_fin = "SELECT * FROM financeiro WHERE usuario_email = :email AND data_entrada BETWEEN :inicio AND :fim ORDER BY data_entrada DESC";
    $stmt_lista = $pdo->prepare($sql_lista_fin);
    $stmt_lista->execute([':email' => $email_logado, ':inicio' => $data_inicio, ':fim' => $data_fim]);
    $entradas_detalhadas = $stmt_lista->fetchAll(PDO::FETCH_ASSOC);

    // --- 2. VENDAS REALIZADAS (Estoque) ---
    $sql_vendas_estoque = "SELECT v.*, e.produto, e.marca, e.modelo, e.valor, e.prateleira, e.caixa, e.gaveta FROM vendas_estoque v JOIN estoque e ON v.id_estoque = e.id_estoque WHERE v.usuario_email = :email AND v.data_venda BETWEEN :inicio AND :fim ORDER BY v.data_venda DESC";
    $stmt_vendas = $pdo->prepare($sql_vendas_estoque);
    $stmt_vendas->execute([':email' => $email_logado, ':inicio' => $data_inicio, ':fim' => $data_fim]);
    $vendas_detalhadas = $stmt_vendas->fetchAll(PDO::FETCH_ASSOC);
    $total_vendas_estoque = 0;
    foreach($vendas_detalhadas as $v) {
        $total_vendas_estoque += ($v['valor'] * $v['quantidade_vendida']);
    }

    // --- 3. GASTOS ---
    $sql_compras = "SELECT * FROM compras 
                    WHERE usuario_email = :email 
                    AND ((concluido = 1 AND data_conclusao BETWEEN :inicio AND :fim) 
                    OR (concluido = 0 AND data_vencimento BETWEEN :inicio AND :fim))
                    ORDER BY concluido ASC, data_vencimento ASC";
    $stmt_compras = $pdo->prepare($sql_compras);
    $stmt_compras->execute([':email' => $email_logado, ':inicio' => $data_inicio, ':fim' => $data_fim]);
    $compras_detalhadas = $stmt_compras->fetchAll(PDO::FETCH_ASSOC);

    $gastos_concluidos = 0;
    $gastos_pendentes = 0;

    foreach($compras_detalhadas as $c) {
        $sub = $c['valor'] * $c['quantidade'];
        if($c['concluido'] == 1) { 
            $gastos_concluidos += $sub; 
        } else { 
            $gastos_pendentes += $sub; 
        }
    }

    // --- 4. PATRIMÔNIO PARADO (apenas o valor do que ainda não foi vendido) ---
    $sql_estoque_parado = "SELECT valor, quantidade, id_estoque FROM estoque WHERE usuario_email = :email";
    $stmt_patr = $pdo->prepare($sql_estoque_parado);
    $stmt_patr->execute([':email' => $email_logado]);
    $patrimonio_estoque = 0;
    while ($row = $stmt_patr->fetch(PDO::FETCH_ASSOC)) {
        $stmt_v = $pdo->prepare("SELECT SUM(quantidade_vendida) FROM vendas_estoque WHERE id_estoque = :id");
        $stmt_v->execute([':id' => $row['id_estoque']]);
        $total_vendido = (int) $stmt_v->fetchColumn();
        $restante = $row['quantidade'] - $total_vendido;
        if ($restante > 0) {
            $patrimonio_estoque += $row['valor'] * $restante;
        }
    }

    // --- CÁLCULOS ---
    $saldo_bruto_total = $total_entradas + $total_vendas_estoque;
    $projecao_final = $saldo_bruto_total - $gastos_concluidos;
    $projecao_prevista = $saldo_bruto_total - ($gastos_concluidos + $gastos_pendentes);

} catch (PDOException $e) {
    die("Erro: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Financeiro</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f7f6; color: #333; padding: 20px; }
        .container { max-width: 1200px; margin: auto; }
        .card { background: white; border: 1px solid #ddd; padding: 15px; margin: 5px; border-radius: 8px; display: inline-block; min-width: 220px; box-shadow: 2px 2px 5px rgba(0,0,0,0.05); vertical-align: top; }
        .positivo { color: #27ae60; font-weight: bold; }
        .negativo { color: #c0392b; font-weight: bold; }
        .pendente { color: #f39c12; font-weight: bold; }
        .destaque { background-color: #e8f4fd; border-left: 5px solid #3498db; }
        .bruto-card { background-color: #f8f9fa; border-left: 5px solid #6c757d; }
        
        table { width: 100%; border-collapse: collapse; background: white; margin-bottom: 30px; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        th, td { padding: 12px; border-bottom: 1px solid #eee; text-align: left; }
        th { background-color: #34495e; color: white; }
        tr:hover { background-color: #f9f9f9; }
        h3 { border-bottom: 2px solid #34495e; padding-bottom: 5px; margin-top: 40px; color: #2c3e50; }
        .status-badge { padding: 4px 8px; border-radius: 4px; font-size: 0.85em; text-transform: uppercase; }
        .btn-acao { text-decoration: none; font-size: 0.9em; font-weight: bold; }
        .edit { color: #3498db; }
        .delete { color: #e74c3c; margin-left: 10px; }
    </style>
</head>
<body>

<div class="container">
    <h2>📊 Dashboard de Controle Financeiro</h2>

    <fieldset style="border-radius: 8px; border: 1px solid #ccc; background: white;">
        <legend>Período de Análise</legend>
        <form method="GET">
            <input type="date" name="d_inicio" value="<?php echo $data_inicio; ?>"> até 
            <input type="date" name="d_fim" value="<?php echo $data_fim; ?>">
            <button type="submit" style="cursor:pointer">Atualizar Relatório</button>
            <a href="financeiro.php">Limpar Filtros</a>
        </form>
    </fieldset>

    <br>

    <div class="card">
        <strong>💰 Entradas:</strong><br>
        <span class="positivo">R$ <?php echo number_format($total_entradas, 2, ',', '.'); ?></span>
    </div>
    <div class="card">
        <strong>📦 Vendas Estoque:</strong><br>
        <span class="positivo">R$ <?php echo number_format($total_vendas_estoque, 2, ',', '.'); ?></span>
    </div>
    <div class="card">
        <strong>📉 Saídas:</strong><br>
        <span class="negativo">R$ <?php echo number_format($gastos_concluidos, 2, ',', '.'); ?></span>
    </div>
    <div class="card">
        <strong>⏳ Gastos Previstos:</strong><br>
        <span class="pendente">R$ <?php echo number_format($gastos_pendentes, 2, ',', '.'); ?></span>
    </div>
    <div class="card" style="background: #fff3cd;">
        <strong>🏠 Patrimônio em Estoque:</strong><br>
        <span style="color: #856404; font-weight: bold;">R$ <?php echo number_format($patrimonio_estoque, 2, ',', '.'); ?></span>
    </div>

    <div style="margin-top: 20px;">
        <div class="card bruto-card" style="min-width: 300px;">
            <strong>💰 SALDO BRUTO TOTAL:</strong><br>
            <small>(Entradas + Vendas Realizadas)</small><br>
            <span style="font-size: 1.6em; color: #2c3e50;">
                R$ <?php echo number_format($saldo_bruto_total, 2, ',', '.'); ?>
            </span>
        </div>

        <div class="card destaque" style="min-width: 300px;">
            <strong>✅ PROJEÇÃO FINAL (REALIZADO):</strong><br>
            <small>Saldo Bruto - Gastos Pagos</small><br>
            <span class="<?php echo ($projecao_final >= 0) ? 'positivo' : 'negativo'; ?>" style="font-size: 1.6em;">
                R$ <?php echo number_format($projecao_final, 2, ',', '.'); ?>
            </span>
        </div>

        <div class="card destaque" style="min-width: 300px; border-left-color: #f39c12;">
            <strong>⏳ PROJEÇÃO PREVISTA (COMPLETA):</strong><br>
            <small>Saldo Bruto - Todos os Gastos</small><br>
            <span class="<?php echo ($projecao_prevista >= 0) ? 'positivo' : 'negativo'; ?>" style="font-size: 1.6em;">
                R$ <?php echo number_format($projecao_prevista, 2, ',', '.'); ?>
            </span>
        </div>
    </div>

    <h3>✅ Entradas: Salários, Serviços e Outros Recebimentos</h3>
    <table>
        <thead>
            <tr>
                <th>Data</th>
                <th>Tipo</th>
                <th>Descrição</th>
                <th>Valor</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($entradas_detalhadas as $item): ?>
            <tr>
                <td><?php echo date('d/m/Y', strtotime($item['data_entrada'])); ?></td>
                <td><small><?php echo $item['tipo']; ?></small></td>
                <td><?php echo isset($item['modelo']) ? htmlspecialchars($item['modelo']) : htmlspecialchars($item['descricao']); ?></td>
                <td class="positivo">R$ <?php echo number_format($item['valor'], 2, ',', '.'); ?></td>
                <td>
                    <a href="editar_financeiro.php?id=<?php echo $item['id_financeiro']; ?>" class="btn-acao edit">Editar</a>
                    <a href="deletar.php?tipo=financeiro&id=<?php echo $item['id_financeiro']; ?>" class="btn-acao delete" onclick="return confirm('Excluir este registro financeiro?')">Deletar</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h3>📦 Vendas Realizadas (Estoque)</h3>
    <table>
        <thead>
            <tr>
                <th>Data</th>
                <th>Produto</th>
                <th>Marca/Modelo</th>
                <th>Local</th>
                <th>Qtd Vendida</th>
                <th>Valor Un.</th>
                <th>Total</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($vendas_detalhadas as $v): $sub = $v['valor'] * $v['quantidade_vendida']; 
                $loc_parts = [];
                if (!empty($v['prateleira'])) $loc_parts[] = $v['prateleira'];
                if (!empty($v['caixa'])) $loc_parts[] = $v['caixa'];
                if (!empty($v['gaveta'])) $loc_parts[] = $v['gaveta'];
                $loc_exibicao = count($loc_parts) ? implode(' | ', $loc_parts) : "-";
            ?>
            <tr>
                <td><?php echo date('d/m/Y', strtotime($v['data_venda'])); ?></td>
                <td><strong><?php echo htmlspecialchars($v['produto']); ?></strong></td>
                <td><?php echo htmlspecialchars($v['marca'] . " " . $v['modelo']); ?></td>
                <td><?php echo htmlspecialchars($loc_exibicao); ?></td>
                <td><?php echo $v['quantidade_vendida']; ?></td>
                <td>R$ <?php echo number_format($v['valor'], 2, ',', '.'); ?></td>
                <td class="positivo">R$ <?php echo number_format($sub, 2, ',', '.'); ?></td>
                <td>
                    <a href="editar_estoque.php?id=<?php echo $v['id_estoque']; ?>" class="btn-acao edit">Editar</a>
                    <a href="deletar.php?tipo=venda&id=<?php echo $v['id']; ?>" class="btn-acao delete" onclick="return confirm('Deseja extornar esta venda? A quantidade será devolvida ao estoque.')">Extornar</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h3>💸 Lista de Saídas</h3>
    <table>
        <thead>
            <tr>
                <th>Status</th>
                <th>Produto / Serviço</th>
                <th>Marca / Empresa</th>
                <th>Modelo / Descrição</th>
                <th>Parcela</th>
                <th>Vencimento</th>
                <th>Subtotal</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($compras_detalhadas as $c): $sub = $c['valor'] * $c['quantidade']; ?>
            <tr>
                <td>
                    <?php if($c['concluido']): ?>
                        <span class="status-badge" style="background: #d4edda; color: #155724;">Pago</span>
                    <?php else: ?>
                        <span class="status-badge" style="background: #fff3cd; color: #856404;">Pendente</span>
                    <?php endif; ?>
                </td>
                <td><?php echo htmlspecialchars($c['produto']); ?> <small>(<?php echo $c['quantidade']; ?>x)</small></td>
                <td><?php echo htmlspecialchars($c['marca'] ?? '---'); ?></td>
                <td><?php echo htmlspecialchars($c['modelo'] ?? '---'); ?></td>
                <td><strong><?php echo ($c['parcelas_total'] > 1) ? $c['parcela_atual'].'/'.$c['parcelas_total'] : 'À Vista'; ?></strong></td>
                <td><?php echo date('d/m/Y', strtotime($c['data_vencimento'])); ?></td>
                <td class="negativo">R$ <?php echo number_format($sub, 2, ',', '.'); ?></td>
                <td>
                    <a href="editar_compras.php?id=<?php echo $c['id_compra']; ?>" class="btn-acao edit">Editar</a>
                    <a href="deletar.php?tipo=compras&id=<?php echo $c['id_compra']; ?>" class="btn-acao delete" onclick="return confirm('Excluir este gasto?')">Deletar</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div style="margin-bottom: 50px;">
        <a href="adicionar_financeiro.php"><b>+ Registrar Salário/Serviço</b></a> | 
        <a href="adicionar_compra.php"><b>+ Nova Compra</b></a> |
        <a href="adicionar_estoque.php"><b>+ Adicionar ao Estoque</b></a><br><br>
        <a href="index.php">Voltar ao Início</a>
    </div>
</div>

</body>
</html>