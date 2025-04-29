<?php
require_once 'config.php';

echo "Conteúdo de \$_GET recebido em marcar_concluido.php:<br>";
var_dump($_GET);
echo "<br>";

if (isset($_GET['tipo']) && isset($_GET['id']) && isset($_GET['action'])) {
    $tipo = $_GET['tipo'];
    $id = $_GET['id'];
    $action = $_GET['action'];

    $tabela = '';
    $id_coluna = '';
    $redirect_pagina = '';
    $concluido_status = null;
    if ($tipo === 'compras') {
        $tabela = 'compras';
        $id_coluna = 'id_compra';
        $redirect_pagina = 'listar_compras.php';
    } elseif ($tipo === 'compromissos') {
        $tabela = 'compromissos';
        $id_coluna = 'id_compromissos';
        $redirect_pagina = 'listar_compromissos.php';
    } else {

         die("Tipo de operação inválido.");
    }
    if ($action === 'marcar') {
        $concluido_status = TRUE;
    } elseif ($action === 'desmarcar') {
        $concluido_status = FALSE;
    } else {

        die("Ação inválida.");
    }
    if ($tabela && $concluido_status !== null) {
        try {
            $stmt = $pdo->prepare("UPDATE $tabela SET concluido = :concluido_status WHERE $id_coluna = :id");
            $stmt->bindParam(':concluido_status', $concluido_status, PDO::PARAM_BOOL);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            header("Location: " . $redirect_pagina);
            exit();

        } catch (PDOException $e) {
            echo "Erro ao executar ação: " . $e->getMessage();
        }
    } else {
        echo "Erro interno: Dados insuficientes ou inválidos para a ação.";
    }
} else {
    echo "Parâmetros ausentes.";
}
?>