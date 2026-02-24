<?php
// Inicia a sessão apenas se ela ainda não tiver sido iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * VERIFICAÇÃO DE SEGURANÇA
 * Se a variável de sessão 'user_email' não estiver definida,
 * significa que o usuário não passou pelo login do Google.
 */
if (!isset($_SESSION['user_email'])) {
    // Redireciona imediatamente para a página inicial de login
    header("Location: index.php");
    exit(); // Interrompe a execução do script para que nada abaixo seja carregado
}

// Opcional: Você pode definir uma variável para usar o nome do usuário em qualquer página
$nomeUsuarioLogado = $_SESSION['user_name'] ?? 'Usuário';
?>