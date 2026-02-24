<?php
require_once 'config.php';
session_start();

if (isset($_GET['token'])) {
    $token = $_GET['token'];

    // O token JWT tem 3 partes. A segunda (índice 1) tem os dados do perfil.
    $partes = explode('.', $token);
    
    if (count($partes) === 3) {
        // Decodifica a Base64URL do Google
        $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $partes[1])), true);

        if ($payload && isset($payload['email'])) {
            // Salva os dados na sessão
            $_SESSION['user_email']   = $payload['email'];
            $_SESSION['user_name']    = $payload['given_name'] ?? $payload['name'];
            $_SESSION['user_picture'] = $payload['picture'] ?? '';

            // Login bem-sucedido!
            header("Location: index.php");
            exit();
        }
    }
}

// Se falhar, volta para o login com aviso
header("Location: index.php?erro=falha_na_autenticacao");
exit();