<?php
require_once 'config.php';

if (!isset($_POST['tipo']) || !isset($_POST['id'])) {
    die("Parâmetros ausentes.");
}

$tipo = $_POST['tipo'];
$id = $_POST['id'];

$tabela = '';
$id_coluna = '';
$redirect_pagina = '';
$sql = '';
$bind_params = [':id' => $id];

if ($tipo === 'compras') {
    $tabela = 'compras';
    $id_coluna = 'id_compra';
    $redirect_pagina = 'listar_compras.php';
    
    if (!isset($_POST['produto']) || !isset($_POST['quantidade'])) {
        die("Dados de compra ausentes.");
    }
    $produto = $_POST['produto'];
    $quantidade = $_POST['quantidade'];

    $sql = "UPDATE $tabela SET produto = :produto, quantidade = :quantidade WHERE $id_coluna = :id";
    $bind_params[':produto'] = $produto;
    $bind_params[':quantidade'] = $quantidade;

} elseif ($tipo === 'compromissos') {
    $tabela = 'compromissos';
    $id_coluna = 'id_compromissos';
    $redirect_pagina = 'listar_compromissos.php';

     if (!isset($_POST['descricao']) || !isset($_POST['data'])) {
         die("Dados de compromisso ausentes.");
     }
    $descricao = $_POST['descricao'];
    $data = $_POST['data'];
    $hora = $_POST['hora'];

    $sql = "UPDATE $tabela SET descricao = :descricao, data = :data, hora = :hora WHERE $id_coluna = :id";
    $bind_params[':descricao'] = $descricao;
    $bind_params[':data'] = $data;
    $bind_params[':hora'] = $hora !== '' ? $hora : NULL;

} else {
    die("Tipo de atualização inválido.");
}

if ($sql) {
    try {
        $stmt = $pdo->prepare($sql);

        foreach ($bind_params as $param => &$value) {
             $param_type = PDO::PARAM_STR;
             if (is_int($value)) {
                 $param_type = PDO::PARAM_INT;
             } elseif (is_bool($value)) {
                  $param_type = PDO::PARAM_BOOL;
             } elseif (is_null($value)) {
                  $param_type = PDO::PARAM_NULL;
             }
             if ($param === ':id' || ($tipo === 'compras' && $param === ':quantidade')) {
                  $param_type = PDO::PARAM_INT;
             }

            $stmt->bindParam($param, $value, $param_type);
        }

        $stmt->execute();

        header("Location: " . $redirect_pagina);
        exit();

    } catch (PDOException $e) {
        echo "Erro ao atualizar: " . $e->getMessage();
    }
} else {
    echo "Erro interno: Query SQL não definida.";
}
?>