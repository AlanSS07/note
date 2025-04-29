<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $descricao = $_POST['descricao'];
    $data = $_POST['data'];
    $hora = $_POST['hora'];

    
    try {
        $stmt = $pdo->prepare("INSERT INTO compromissos (descricao, data, hora) VALUES (:descricao, :data, :hora)");
        $stmt->bindParam(':descricao', $descricao);
        $stmt->bindParam(':data', $data);
        $stmt->bindParam(':hora', $hora);
        $stmt->execute();
        header("Location: listar_compromissos.php");
        exit();
    } catch (PDOException $e) {
        echo "Erro ao salvar compromisso: " . $e->getMessage();
    }
} else {
    header("Location: adicionar_compromisso.html");
    exit();
}
?>