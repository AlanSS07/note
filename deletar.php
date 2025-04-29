<?php
require_once 'config.php';

if (isset($_GET['tipo']) && isset($_GET['id'])) {
    $tipo = $_GET['tipo'];
    $id = $_GET['id'];
    $tabela = '';
    $id_coluna = '';
    $redirect_pagina = '';

    if ($tipo === 'compras') {
        $tabela = 'compras';
        $id_coluna = 'id_compra';
        $redirect_pagina = 'listar_compras.php';
    } elseif ($tipo === 'compromissos') {
        $tabela = 'compromissos';
        $id_coluna = 'id_compromissos';
        $redirect_pagina = 'listar_compromissos.php';
    }

    if ($tabela) {
        try {
            $stmt = $pdo->prepare("DELETE FROM $tabela WHERE $id_coluna = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            header("Location: " . $redirect_pagina);
            exit();
        } catch (PDOException $e) {
            echo "Erro ao deletar: " . $e->getMessage();
        }
    } else {
        echo "Tipo de operação inválido.";
    }
} else {
    echo "Parâmetros ausentes.";
}
?>