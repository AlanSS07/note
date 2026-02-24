<?php 
require_once 'config.php';
include 'session.php'; // Já contém session_start() e protege a página
require_once 'functions.php'; 

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 1. Padronização (Sua regra: Maiúsculas para descrição/origem)
    // Usamos padronizarTexto para garantir que "Salário" vire "SALÁRIO" corretamente
    $descricao    = padronizarTexto($_POST['descricao']);
    $tipo         = $_POST['tipo'];
    $valor        = $_POST['valor'];
    $data_entrada = $_POST['data_entrada'];
    $email        = $_SESSION['user_email']; // Identifica o dono da receita

    try {
        // 2. SQL com vínculo de e-mail para isolamento multiusuário
        $sql = "INSERT INTO financeiro (descricao, tipo, valor, data_entrada, usuario_email) 
                VALUES (:descricao, :tipo, :valor, :data_entrada, :email)";
        
        $stmt = $pdo->prepare($sql);
        
        $stmt->bindParam(':descricao', $descricao);
        $stmt->bindParam(':tipo', $tipo);
        $stmt->bindParam(':valor', $valor);
        $stmt->bindParam(':data_entrada', $data_entrada);
        $stmt->bindParam(':email', $email);

        if ($stmt->execute()) {
            // Redireciona de volta para o painel financeiro
            header("Location: financeiro.php");
            exit();
        }
    } catch (PDOException $e) {
        die("Erro ao salvar registro financeiro: " . $e->getMessage());
    }
} else {
    // Se tentarem acessar o arquivo diretamente sem POST
    header("Location: adicionar_financeiro.php");
    exit();
}
?>