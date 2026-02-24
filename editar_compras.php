<?php 
require_once 'config.php';
include 'session.php'; // Já inicia a sessão e verifica se o usuário está logado


$id = $_GET['id'] ?? die("ID ausente");
$email_logado = $_SESSION['user_email'];

try {
    // ADEQUAÇÃO: Verificamos o ID e se o e-mail do dono bate com o logado
    $stmt = $pdo->prepare("SELECT * FROM compras WHERE id_compra = :id AND usuario_email = :email");
    $stmt->execute([
        ':id' => $id,
        ':email' => $email_logado
    ]);
    
    $item = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Se o ID for de outro usuário, o $item retornará falso
    if (!$item) {
        die("Acesso negado: Este registro não pertence à sua conta ou não existe.");
    }
} catch (PDOException $e) { 
    die("Erro no banco: " . $e->getMessage()); 
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Editar Pagamento</title>
    <style> #marca, #modelo { text-transform: uppercase; } </style>
</head>
<body>
    <h2>Editar Pagamento - Parcela <?php echo $item['parcela_atual']."/".$item['parcelas_total']; ?></h2>
    
    <form method="POST" action="atualizar.php">
        <input type='hidden' name='tipo' value='compras'>
        <input type='hidden' name='id' value='<?php echo $item['id_compra']; ?>'>

        <label>Produto / Serviço:</label><br>
        <input type="text" name="produto" value="<?php echo htmlspecialchars($item['produto']); ?>" required><br><br>

        <label>Marca / Empresa:</label><br>
        <input type="text" id="marca" name="marca" value="<?php echo htmlspecialchars($item['marca']); ?>"><br><br>

        <label>Modelo / Descrição:</label><br>
        <input type="text" id="modelo" name="modelo" value="<?php echo htmlspecialchars($item['modelo']); ?>"><br><br>

        <label>Valor da Parcela (R$):</label><br>
        <input type="number" name="valor" step="0.01" value="<?php echo $item['valor']; ?>"><br><br>

        <label>Quantidade:</label><br>
        <input type="number" name="quantidade" value="<?php echo $item['quantidade']; ?>" min="1" required><br><br>

        <!-- <label>Número de Parcelas:</label><br>
        <input type="number" name="parcelas_total" value="<?php echo $item['parcelas_total']; ?>" min="1" required><br><br> -->

        <label>Data de Vencimento:</label><br>
        <input type="date" name="data_vencimento" value="<?php echo $item['data_vencimento']; ?>"><br><br>

        <label>Data de Pagamento:</label><br>
        <input type="date" name="data_conclusao" value="<?php echo ($item['data_conclusao'] ? date('Y-m-d', strtotime($item['data_conclusao'])) : ''); ?>"><br><br>

        <input type="submit" value="Salvar Alterações">
    </form>
    
    <p><a href='listar_compras.php'>Cancelar</a> | <a href="index.php">Início</a></p>
</body>
</html>