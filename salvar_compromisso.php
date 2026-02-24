<?php 
require_once 'config.php';
include 'session.php'; // Já contém o session_start() e verifica se o usuário está logado

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Dados vindos do formulário
    $descricao = $_POST['descricao'];
    $data      = $_POST['data'];
    $hora      = !empty($_POST['hora']) ? $_POST['hora'] : null;
    $email     = $_SESSION['user_email']; // Identifica quem está salvando

    try {
        // ADEQUAÇÃO: Incluindo o usuario_email para isolamento de dados
        $sql = "INSERT INTO compromissos (descricao, data, hora, concluido, usuario_email) 
                VALUES (:descricao, :data, :hora, 0, :email)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':descricao', $descricao);
        $stmt->bindParam(':data', $data);
        $stmt->bindParam(':hora', $hora);
        $stmt->bindParam(':email', $email);
        
        $stmt->execute();
        
        header("Location: listar_compromissos.php");
        exit();
    } catch (PDOException $e) {
        die("Erro ao salvar compromisso: " . $e->getMessage());
    }
} else {
    header("Location: adicionar_compromisso.php");
    exit();
}
?>