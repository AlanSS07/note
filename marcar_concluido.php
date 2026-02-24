<?php 
require_once 'config.php';
include 'session.php'; // Já contém session_start() e protege a página

if (isset($_GET['tipo']) && isset($_GET['id']) && isset($_GET['action'])) {
    $tipo = $_GET['tipo'];
    $id = $_GET['id'];
    $action = $_GET['action'];
    $email_logado = $_SESSION['user_email']; // Segurança: Identifica o dono da ação

    $tabela = '';
    $id_coluna = '';
    $redirect_pagina = '';
    $coluna_status = 'concluido'; 
    $concluido_status = ($action === 'marcar') ? 1 : 0;

    // 1. Definição de regras por tipo
    if ($tipo === 'compras') {
        $tabela = 'compras';
        $id_coluna = 'id_compra';
        $redirect_pagina = 'listar_compras.php';
    } elseif ($tipo === 'compromissos') {
        $tabela = 'compromissos';
        $id_coluna = 'id_compromissos';
        $redirect_pagina = 'listar_compromissos.php';
    } elseif ($tipo === 'estoque') {
        $tabela = 'estoque';
        $id_coluna = 'id_estoque';
        $redirect_pagina = 'listar_estoque.php';
        $coluna_status = 'vendido'; 
    } else {
         die("Tipo de operação inválido.");
    }

    try {
        // 2. Construção da SQL com trava de segurança (WHERE usuario_email)
        if ($tipo === 'compras') {
            if ($action === 'marcar') {
                $sql = "UPDATE compras SET concluido = :status, data_conclusao = CURDATE() 
                        WHERE id_compra = :id AND usuario_email = :email";
            } else {
                $sql = "UPDATE compras SET concluido = :status, data_conclusao = NULL 
                        WHERE id_compra = :id AND usuario_email = :email";
            }
        } else {
            // Lógica para Estoque e Compromissos
            $sql = "UPDATE $tabela SET $coluna_status = :status 
                    WHERE $id_coluna = :id AND usuario_email = :email";
        }

        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':status', $concluido_status, PDO::PARAM_INT);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':email', $email_logado, PDO::PARAM_STR);
        $stmt->execute();

        // 3. Redirecionamento
        header("Location: " . $redirect_pagina);
        exit();

    } catch (PDOException $e) {
        die("Erro ao executar ação: " . $e->getMessage());
    }
} else {
    echo "Parâmetros ausentes.";
}
?>