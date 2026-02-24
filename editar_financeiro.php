<?php 
require_once 'config.php';
include 'session.php'; // Garante que o usuário está logado

$email_logado = $_SESSION['user_email'];

if (!isset($_GET['id'])) {
    header("Location: financeiro.php");
    exit();
}

$id = $_GET['id'];

try {
    // Segurança: O WHERE id AND usuario_email impede que um usuário edite o financeiro de outro
    $stmt = $pdo->prepare("SELECT * FROM financeiro WHERE id_financeiro = :id AND usuario_email = :email");
    $stmt->execute([':id' => $id, ':email' => $email_logado]);
    $registro = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$registro) {
        die("Registro não encontrado ou você não tem permissão para editá-lo.");
    }
} catch (PDOException $e) {
    die("Erro ao buscar dados: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Editar Entrada Financeira</title>
    <style>
        /* Padronização visual para o campo de descrição */
        #descricao { text-transform: uppercase; }
    </style>
</head>
<body>

    <h2>Editar Registro Financeiro</h2>

    <form action="atualizar.php" method="POST">
        <input type="hidden" name="tipo" value="financeiro">
        <input type="hidden" name="id" value="<?php echo $registro['id_financeiro']; ?>">

        <label for="tipo_entrada">Tipo de Entrada:</label><br>
        <select name="tipo_entrada" id="tipo_entrada" required>
            <option value="Salário" <?php echo ($registro['tipo'] == 'Salário') ? 'selected' : ''; ?>>Salário</option>
            <option value="Serviço" <?php echo ($registro['tipo'] == 'Serviço') ? 'selected' : ''; ?>>Serviço Prestado</option>
            <option value="Outros" <?php echo ($registro['tipo'] == 'Outros') ? 'selected' : ''; ?>>Outros</option>
        </select><br><br>

        <label for="descricao">Descrição / Origem:</label><br>
        <input type="text" id="descricao" name="descricao" 
               value="<?php echo htmlspecialchars($registro['descricao']); ?>" 
               oninput="this.value = this.value.toUpperCase()" required><br><br>

        <label for="valor">Valor (R$):</label><br>
        <input type="number" id="valor" name="valor" step="0.01" min="0.01" 
               value="<?php echo $registro['valor']; ?>" required><br><br>

        <label for="data_entrada">Data do Recebimento:</label><br>
        <input type="date" id="data_entrada" name="data_entrada" 
               value="<?php echo $registro['data_entrada']; ?>" required><br><br>

        <input type="submit" value="Salvar Alterações">
    </form>

    <br>
    <a href="financeiro.php">Cancelar e Voltar</a> | 
    <a href="index.php">Início</a>

</body>
</html>