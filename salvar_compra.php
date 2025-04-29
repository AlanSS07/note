<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $produto = $_POST['produto'];
    $quantidade = $_POST['quantidade'];

    try {
        $stmt = $pdo->prepare("INSERT INTO compras (produto, quantidade) VALUES (:produto, :quantidade)");
        $stmt->bindParam(':produto', $produto);
        $stmt->bindParam(':quantidade', $quantidade, PDO::PARAM_INT);
        $stmt->execute();
        header("Location: listar_compras.php");
        exit();
    } catch (PDOException $e) {
        echo "Erro ao salvar compra: " . $e->getMessage();
    }
} else {
    header("Location: adicionar_compra.html");
    exit();
}
?>