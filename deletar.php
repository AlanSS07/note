<?php 
require_once 'config.php';
include 'session.php'; // Já faz o session_start() e protege a página

if (isset($_GET['tipo']) && isset($_GET['id'])) {
    $tipo = $_GET['tipo'];
    $id = $_GET['id'];
    $email_logado = $_SESSION['user_email']; // Identificador do dono
    
    // Operação de extorno de venda NÃO depende de $tabela
    if ($tipo === 'venda') {
        try {
            // Buscar dados da venda
            $stmt_venda = $pdo->prepare("SELECT id_estoque, quantidade_vendida FROM vendas_estoque WHERE id = :id AND usuario_email = :email");
            $stmt_venda->execute([':id' => $id, ':email' => $email_logado]);
            $venda = $stmt_venda->fetch(PDO::FETCH_ASSOC);
            if ($venda) {
                // Atualizar campo vendido do estoque (se existir)
                $stmt_update = $pdo->prepare("UPDATE estoque SET vendido = vendido - :qtd WHERE id_estoque = :id_estoque");
                $stmt_update->execute([':qtd' => $venda['quantidade_vendida'], ':id_estoque' => $venda['id_estoque']]);
                // Remover a venda
                $stmt_del = $pdo->prepare("DELETE FROM vendas_estoque WHERE id = :id AND usuario_email = :email");
                $stmt_del->execute([':id' => $id, ':email' => $email_logado]);
            }
            header("Location: financeiro.php");
            exit();
        } catch (PDOException $e) {
            echo "Erro ao extornar venda: " . $e->getMessage();
        }
    }

    $tabela = '';
    $id_coluna = '';
    $redirect_pagina = '';

    // Mapeamento das tabelas e colunas (conforme seu SQL)
    if ($tipo === 'compras') {
        $tabela = 'compras';
        $id_coluna = 'id_compra';
        $redirect_pagina = 'listar_compras.php';
    } elseif ($tipo === 'estoque') {
        $tabela = 'estoque';
        $id_coluna = 'id_estoque';
        $redirect_pagina = 'listar_estoque.php';
    } elseif ($tipo === 'compromissos') {
        $tabela = 'compromissos';
        $id_coluna = 'id_compromissos';
        $redirect_pagina = 'listar_compromissos.php';
    } elseif ($tipo === 'financeiro') { // Adicionado para cobrir todos os módulos
        $tabela = 'financeiro';
        $id_coluna = 'id_financeiro';
        $redirect_pagina = 'financeiro.php';
    }

    if ($tabela) {
        try {
            // Se for estoque, remover vendas relacionadas antes
            if ($tipo === 'estoque') {
                $stmt_vendas = $pdo->prepare("DELETE FROM vendas_estoque WHERE id_estoque = :id");
                $stmt_vendas->bindParam(':id', $id, PDO::PARAM_INT);
                $stmt_vendas->execute();
            }
            // SEGURANÇA: Adicionado "AND usuario_email = :email"
            // Isso impede que um usuário delete dados de outro
            $sql = "DELETE FROM $tabela WHERE $id_coluna = :id AND usuario_email = :email";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->bindParam(':email', $email_logado, PDO::PARAM_STR);
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