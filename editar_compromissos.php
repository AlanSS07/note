<?php 
require_once 'config.php';
include 'session.php'; // Já inicia a sessão e verifica o login

if (!isset($_GET['id'])) {
    die("ID do compromisso ausente.");
}

$id = $_GET['id'];
$email_logado = $_SESSION['user_email'];

$item = null;
try {
    // ADEQUAÇÃO: Verificamos o ID e se pertence ao e-mail logado
    $stmt = $pdo->prepare("SELECT id_compromissos, descricao, data, hora FROM compromissos WHERE id_compromissos = :id AND usuario_email = :email");
    $stmt->execute([
        ':id' => $id,
        ':email' => $email_logado
    ]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$item) {
        die("Acesso negado ou compromisso não encontrado.");
    }

} catch (PDOException $e) {
    die("Erro ao carregar dados: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Editar Compromisso</title>
</head>
<body>
    <h2>Editar Lembrete</h2>

    <form method="POST" action="atualizar.php">
        <input type='hidden' name='tipo' value='compromissos'>
        <input type='hidden' name='id' value='<?php echo htmlspecialchars($item['id_compromissos']); ?>'>
        
        <label for="descricao">Descrição:</label><br>
        <input type="text" id="descricao" name="descricao" value="<?php echo htmlspecialchars($item['descricao']); ?>" required><br><br>

        <label for="data">Data:</label><br>
        <input type="date" id="data" name="data" value="<?php echo htmlspecialchars($item['data']); ?>" required><br><br>

        <label for="hora">Hora:</label><br>
        <input type="time" id="hora" name="hora" value="<?php echo htmlspecialchars($item['hora']); ?>"><br><br>

        <input type="submit" value="Salvar">
    </form>

    <p>
        <a href='listar_compromissos.php'>Cancelar</a>
    </p>
    <br>
    <hr/>
    <p><a href="index.php">Início</a></p>
</body>
</html>