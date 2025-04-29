<?php
require_once 'config.php';

if (!isset($_GET['id'])) {
    die("ID da compra ausente.");
}

$id = $_GET['id'];

$item = null;
try {
    $stmt = $pdo->prepare("SELECT id_compra, produto, quantidade FROM compras WHERE id_compra = :id");
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$item) {
        die("Compra não encontrada.");
    }

} catch (PDOException $e) {
    die("Erro ao carregar dados da compra para edição: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Editar Compra</title>
</head>
<body>
    <h2>Editar Compra</h2>

    <form method="POST" action="atualizar.php">
        <?php
        echo "<input type='hidden' name='tipo' value='compras'>";
        echo "<input type='hidden' name='id' value='" . htmlspecialchars($item['id_compra']) . "'>";
        ?>
        <label for="produto">Produto:</label><br>
        <input type="text" id="produto" name="produto" value="<?php echo htmlspecialchars($item['produto']); ?>" required><br><br>

        <label for="quantidade">Quantidade:</label><br>
        <input type="number" id="quantidade" name="quantidade" value="<?php echo htmlspecialchars($item['quantidade']); ?>" required><br><br>

        <input type="submit" value="Salvar">
    </form>

    <p>
        <a href='listar_compras.php'>Cancelar</a>
    </p>
    </br>
    <hr/>
    <p><a href="index.html">Inicio</a></p>
</body>
</html>