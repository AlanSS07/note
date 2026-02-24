<?php 
require_once 'config.php';
include 'session.php'; // Já faz session_start() e protege a página

if (!isset($_GET['id'])) {
    die("ID do estoque ausente.");
}

$id = $_GET['id'];
$email_logado = $_SESSION['user_email'];

try {
    // ADEQUAÇÃO: Verificamos o ID e se o item pertence ao usuário logado
    $stmt = $pdo->prepare("SELECT id_estoque, produto, marca, modelo, valor, quantidade, prateleira, caixa, gaveta 
                           FROM estoque 
                           WHERE id_estoque = :id AND usuario_email = :email");
    $stmt->execute([
        ':id' => $id,
        ':email' => $email_logado
    ]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$item) {
        die("Acesso negado ou item não encontrado no seu estoque.");
    }

    // Calcula a localização atual para exibir no input unificado
    $local_atual = (!empty($item['prateleira']) && $item['prateleira'] !== '0') ? $item['prateleira'] : ((!empty($item['caixa']) && $item['caixa'] !== '0') ? $item['caixa'] : '');

    // Buscar total vendido para este produto
    $stmt_v = $pdo->prepare("SELECT SUM(quantidade_vendida) FROM vendas_estoque WHERE id_estoque = :id");
    $stmt_v->execute([':id' => $item['id_estoque']]);
    $total_vendido = (int) $stmt_v->fetchColumn();
    $restante = $item['quantidade'] - $total_vendido;

    // Sugestões de Datalist (Filtrando também para sugerir apenas locais que o próprio usuário já usou)
    $locais_padrao = [];
    for ($i = 1; $i <= 20; $i++) { 
        $locais_padrao[] = "Prateleira $i"; 
        $locais_padrao[] = "Caixa $i"; 
    }
    
    // ADEQUAÇÃO: Sugestões de locais baseadas apenas nos dados deste usuário
    $stmt_locais = $pdo->prepare("SELECT DISTINCT prateleira FROM estoque WHERE usuario_email = :email AND prateleira IS NOT NULL AND prateleira != '' 
                                  UNION 
                                  SELECT DISTINCT caixa FROM estoque WHERE usuario_email = :email AND caixa IS NOT NULL AND caixa != ''");
    $stmt_locais->execute([':email' => $email_logado]);
    $locais_bd = $stmt_locais->fetchAll(PDO::FETCH_COLUMN);
    
    $todos_locais = array_unique(array_merge($locais_padrao, $locais_bd));
    sort($todos_locais);

} catch (PDOException $e) {
    die("Erro ao carregar dados: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Editar Estoque</title>
</head>
<body>
    <h2>Editar Item do Estoque</h2>

    <form method="POST" action="atualizar.php">
        
        <input type='hidden' name='tipo' value='estoque'>
        <input type='hidden' name='id' value='<?php echo htmlspecialchars($item['id_estoque']); ?>'>

        <label for="produto">Produto:</label><br>
        <input type="text" id="produto" name="produto" value="<?php echo htmlspecialchars($item['produto']); ?>" oninput="this.value = this.value.toUpperCase()" required><br><br>

        <label for="marca">Marca:</label><br>
        <input type="text" id="marca" name="marca" value="<?php echo htmlspecialchars($item['marca']); ?>" oninput="this.value = this.value.toUpperCase()"><br><br>

        <label for="modelo">Modelo:</label><br>
        <input type="text" id="modelo" name="modelo" value="<?php echo htmlspecialchars($item['modelo']); ?>" oninput="this.value = this.value.toUpperCase()"><br><br>

        <label for="prateleira">Prateleira:</label><br>
        <input type="text" id="prateleira" name="prateleira" value="<?php echo htmlspecialchars($item['prateleira']); ?>" placeholder="Ex: 1 ou Prateleira 1"><br><br>

        <label for="caixa">Caixa:</label><br>
        <input type="text" id="caixa" name="caixa" value="<?php echo htmlspecialchars($item['caixa']); ?>" placeholder="Ex: 5 ou Caixa 5"><br><br>

        <label for="gaveta">Gaveta:</label><br>
        <input type="text" id="gaveta" name="gaveta" value="<?php echo htmlspecialchars($item['gaveta'] ?? ''); ?>" placeholder="Ex: 2 ou Gaveta 2"><br><br>

        <label for="valor">Valor (R$):</label><br>
        <input type="number" id="valor" name="valor" step="0.01" value="<?php echo $item['valor']; ?>" required><br><br>


        <label for="quantidade">Quantidade:</label><br>
        <input type="number" id="quantidade" name="quantidade" value="<?php echo $item['quantidade']; ?>" min="<?php echo $total_vendido; ?>" required>
        <small>Total vendido: <strong><?php echo $total_vendido; ?></strong> | Restante: <strong><?php echo $restante; ?></strong></small><br><br>

        <input type="submit" value="Salvar Alterações">

    <?php if ($total_vendido > 0): ?>
        <h4>Histórico de Vendas deste Produto</h4>
        <ul>
        <?php
        $stmt_hist = $pdo->prepare("SELECT quantidade_vendida, data_venda FROM vendas_estoque WHERE id_estoque = :id ORDER BY data_venda DESC");
        $stmt_hist->execute([':id' => $item['id_estoque']]);
        foreach ($stmt_hist as $venda) {
            echo '<li>' . $venda['quantidade_vendida'] . ' vendido(s) em ' . date('d/m/Y', strtotime($venda['data_venda'])) . '</li>';
        }
        ?>
        </ul>
    <?php endif; ?>
    </form>

    <p><a href='listar_estoque.php'>Cancelar</a></p>
    <br/><hr/><p><a href="index.php">Início</a></p>
</body>
</html>