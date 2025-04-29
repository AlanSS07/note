<?php
require_once 'config.php';

if (!isset($_GET['id'])) {
    die("ID do compromisso ausente.");
}

$id = $_GET['id'];

$item = null;
try {
    $stmt = $pdo->prepare("SELECT id_compromissos, descricao, data, hora FROM compromissos WHERE id_compromissos = :id");
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$item) {
        die("Compromisso não encontrado.");
    }

} catch (PDOException $e) {
    die("Erro ao carregar dados do compromisso para edição: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Editar Compromisso</title>
</head>
<body>
    <h2>Editar Lembrete</h2>

    <form method="POST" action="atualizar.php">
        <?php
        echo "<input type='hidden' name='tipo' value='compromissos'>";
        echo "<input type='hidden' name='id' value='" . htmlspecialchars($item['id_compromissos']) . "'>";
        ?>
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
    </br>
    <hr/>
    <p><a href="index.html">Inicio</a></p>
</body>
</html>