<?php 
require_once 'config.php';
include 'session.php'; // Já contém session_start() e protege a página
require_once 'functions.php'; 


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 1. Padronização automática (Sua regra: Maiúsculas)
    $produto    = padronizarTexto($_POST['produto']);
    $marca      = padronizarTexto($_POST['marca']);
    $modelo     = padronizarTexto($_POST['modelo']);
    
    $valor      = $_POST['valor'];
    $quantidade = $_POST['quantidade'];
    $email      = $_SESSION['user_email']; // Identifica o dono do item

    // 2. Localização composta
    $prateleira = $_POST['prateleira'] ?? null;
    $caixa = $_POST['caixa'] ?? null;
    $gaveta = $_POST['gaveta'] ?? null;
    // Prefixar automaticamente se necessário
    if ($prateleira !== null && $prateleira !== '' && stripos($prateleira, 'prateleira') !== 0) {
        $prateleira = 'Prateleira ' . $prateleira;
    }
    if ($caixa !== null && $caixa !== '' && stripos($caixa, 'caixa') !== 0) {
        $caixa = 'Caixa ' . $caixa;
    }
    if ($gaveta !== null && $gaveta !== '' && stripos($gaveta, 'gaveta') !== 0) {
        $gaveta = 'Gaveta ' . $gaveta;
    }

    try {
        // 3. SQL com vínculo de e-mail e status padrão 'vendido = 0'
        $sql = "INSERT INTO estoque (produto, marca, modelo, valor, quantidade, prateleira, caixa, gaveta, vendido, usuario_email) 
            VALUES (:produto, :marca, :modelo, :valor, :quantidade, :prateleira, :caixa, :gaveta, 0, :email)";
        
        $stmt = $pdo->prepare($sql);
        
        $stmt->bindParam(':produto', $produto);
        $stmt->bindParam(':marca', $marca);
        $stmt->bindParam(':modelo', $modelo);
        $stmt->bindParam(':valor', $valor);
        $stmt->bindParam(':quantidade', $quantidade);
        $stmt->bindParam(':prateleira', $prateleira);
        $stmt->bindParam(':caixa', $caixa);
        $stmt->bindParam(':gaveta', $gaveta);
        $stmt->bindParam(':email', $email);

        if ($stmt->execute()) {
            header("Location: listar_estoque.php");
            exit();
        }
    } catch (PDOException $e) {
        die("Erro ao salvar no estoque: " . $e->getMessage());
    }
} else {
    header("Location: adicionar_estoque.php");
    exit();
}
?>