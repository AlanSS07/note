<?php 
require_once 'config.php';
include 'session.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email_logado = $_SESSION['user_email'];

    $produto = $_POST['produto'];
    
    // Padronização automática: tudo que entra vai para MAIÚSCULO
    $produto = mb_strtoupper(trim($_POST['produto']), 'UTF-8');
    $marca   = mb_strtoupper(trim($_POST['marca']), 'UTF-8');
    $modelo  = mb_strtoupper(trim($_POST['modelo']), 'UTF-8');
    
    $valor   = $_POST['valor'];

    // CORREÇÃO: Garante que a quantidade seja um número inteiro. 
    // Se vier vazio, assume 1 (ou 0, dependendo da sua regra de negócio).
    $qtd = !empty($_POST['quantidade']) ? (int)$_POST['quantidade'] : 1;

    $p_total = (int)$_POST['parcelas_total'];
    $data_v  = $_POST['data_vencimento'];

    try {
        // Normalize data_conclusao input: pode ser um array (por parcelas) ou um único valor
        $data_conclusoes = [];
        if (isset($_POST['data_conclusao'])) {
            if (is_array($_POST['data_conclusao'])) {
                $data_conclusoes = $_POST['data_conclusao'];
            } else {
                $data_conclusoes = [$_POST['data_conclusao']];
            }
        }

        $sql = "INSERT INTO compras (produto, marca, modelo, valor, quantidade, concluido, parcelas_total, parcela_atual, data_vencimento, data_conclusao, usuario_email) 
                VALUES (:prod, :marca, :mod, :val, :qtd, :concluido, :p_total, :p_atual, :data_v, :data_concl, :email)";

        $stmt = $pdo->prepare($sql);

        for ($i = 0; $i < $p_total; $i++) {
            $vencimento = date('Y-m-d', strtotime("+$i month", strtotime($data_v)));
            $p_atual = $i + 1;

            $data_concl = isset($data_conclusoes[$i]) && !empty($data_conclusoes[$i]) ? $data_conclusoes[$i] : null;
            $concluido = $data_concl ? 1 : 0;

            $stmt->execute([
                ':prod'    => $produto,
                ':marca'   => $marca,
                ':mod'     => $modelo,
                ':val'     => $valor,
                ':qtd'     => $qtd,
                ':p_total' => $p_total,
                ':p_atual' => $p_atual,
                ':data_v'  => $vencimento,
                ':data_concl' => $data_concl,
                ':concluido' => $concluido,
                ':email'   => $email_logado 
            ]);
        }

        header("Location: listar_compras.php");
        exit();
    } catch (PDOException $e) {
        die("Erro ao salvar: " . $e->getMessage());
    }
}
?>