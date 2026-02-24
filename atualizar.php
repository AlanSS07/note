<?php 
require_once 'config.php';
include 'session.php'; // Já verifica login e inicia sessão
require_once 'functions.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $tipo = $_POST['tipo'];
    $id   = $_POST['id'];
    $email_logado = $_SESSION['user_email'];

    try {
        if ($tipo === 'compras') {
            // PADRONIZAÇÃO: Produto, Marca e Modelo em MAIÚSCULO
            $produto    = mb_strtoupper(trim($_POST['produto']), 'UTF-8');
            $marca      = mb_strtoupper(trim($_POST['marca']), 'UTF-8');
            $modelo     = mb_strtoupper(trim($_POST['modelo']), 'UTF-8');
            
            $valor      = $_POST['valor'];
            $quantidade = $_POST['quantidade'];
            
            $parcelas_total = isset($_POST['parcelas_total']) ? (int)$_POST['parcelas_total'] : $compra_atual['parcelas_total'];
            $data_vencimento = $_POST['data_vencimento'];
            
            // LÓGICA DE STATUS: Se preencheu a data, concluido = 1, senão = 0
            if (array_key_exists('data_conclusao', $_POST)) {
                $data_conclusao = !empty($_POST['data_conclusao']) ? $_POST['data_conclusao'] : null;
            } else {
                // Mantém o valor atual do banco se não enviado
                $data_conclusao = $compra_atual['data_conclusao'] ?? null;
            }
            $concluido = ($data_conclusao !== null) ? 1 : 0;
            
            // 1. Buscamos a informação atual para o caso de novas parcelas
            $stmt_info = $pdo->prepare("SELECT parcela_atual, parcelas_total FROM compras WHERE id_compra = :id AND usuario_email = :email");
            $stmt_info->execute([':id' => $id, ':email' => $email_logado]);
            $compra_atual = $stmt_info->fetch(PDO::FETCH_ASSOC);

            // 2. UPDATE atualizado: Incluindo a coluna 'concluido'
            $sql = "UPDATE compras SET 
                    produto = :produto, marca = :marca, modelo = :modelo, 
                    valor = :valor, quantidade = :quantidade, concluido = :concluido,
                    parcelas_total = :parcelas_total, data_vencimento = :data_vencimento, data_conclusao = :data 
                    WHERE id_compra = :id AND usuario_email = :email";
            
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':produto', $produto);
            $stmt->bindParam(':marca', $marca);
            $stmt->bindParam(':modelo', $modelo);
            $stmt->bindParam(':valor', $valor);
            $stmt->bindParam(':quantidade', $quantidade);
            $stmt->bindParam(':concluido', $concluido); // Vincula o novo status
            $stmt->bindParam(':parcelas_total', $parcelas_total);
            $stmt->bindParam(':data_vencimento', $data_vencimento);
            $stmt->bindParam(':data', $data_conclusao);
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':email', $email_logado);
            $stmt->execute();

            // 3. Lógica para parcelas extras (mantida do anterior)
            if ($compra_atual && $parcelas_total > $compra_atual['parcelas_total']) {
                $parcelas_a_adicionar = $parcelas_total - $compra_atual['parcelas_total'];
                $sql_insert = "INSERT INTO compras (produto, marca, modelo, valor, quantidade, concluido, parcelas_total, parcela_atual, data_vencimento, usuario_email) 
                               VALUES (:prod, :marca, :mod, :val, :qtd, 0, :p_total, :p_atual, :data_v, :email)";
                $stmt_insert = $pdo->prepare($sql_insert);

                for ($i = 1; $i <= $parcelas_a_adicionar; $i++) {
                    $nova_parcela = $compra_atual['parcelas_total'] + $i;
                    $meses_diff = $nova_parcela - $compra_atual['parcela_atual'];
                    $novo_vencimento = date('Y-m-d', strtotime("+$meses_diff month", strtotime($data_vencimento)));

                    $stmt_insert->execute([
                        ':prod'    => $produto,
                        ':marca'   => $marca,
                        ':mod'     => $modelo,
                        ':val'     => $valor,
                        ':qtd'     => $quantidade,
                        ':p_total' => $parcelas_total,
                        ':p_atual' => $nova_parcela,
                        ':data_v'  => $novo_vencimento,
                        ':email'   => $email_logado
                    ]);
                }
            }

        } elseif ($tipo === 'estoque') {
            // Aplicando maiúsculas também no estoque para manter o padrão
            $produto    = mb_strtoupper(trim($_POST['produto']), 'UTF-8');
            $marca      = mb_strtoupper(trim($_POST['marca']), 'UTF-8');
            $modelo     = mb_strtoupper(trim($_POST['modelo']), 'UTF-8');
            $valor      = $_POST['valor'];
            $quantidade = $_POST['quantidade'];
            $prateleira = $_POST['prateleira'] ?? null;
            $caixa      = $_POST['caixa'] ?? null;
            $gaveta     = $_POST['gaveta'] ?? null;
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

                $sql = "UPDATE estoque SET 
                    produto = :produto, marca = :marca, modelo = :modelo, 
                    valor = :valor, quantidade = :quantidade, prateleira = :prateleira, caixa = :caixa, gaveta = :gaveta 
                    WHERE id_estoque = :id AND usuario_email = :email";
            
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':produto', $produto);
            $stmt->bindParam(':marca', $marca);
            $stmt->bindParam(':modelo', $modelo);
            $stmt->bindParam(':valor', $valor);
            $stmt->bindParam(':quantidade', $quantidade);
            $stmt->bindParam(':prateleira', $prateleira);
            $stmt->bindParam(':caixa', $caixa);
            $stmt->bindParam(':gaveta', $gaveta);
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':email', $email_logado);
            $stmt->execute();

        } elseif ($tipo === 'financeiro') {
            // Descrição em maiúsculo no financeiro também
            $descricao    = mb_strtoupper(trim($_POST['descricao']), 'UTF-8');
            $tipo_ent     = $_POST['tipo_entrada'];
            $valor        = $_POST['valor'];
            $data_entrada = $_POST['data_entrada'];

            $sql = "UPDATE financeiro SET 
                    descricao = :descricao, tipo = :tipo_ent, valor = :valor, data_entrada = :data 
                    WHERE id_financeiro = :id AND usuario_email = :email";
            
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':descricao', $descricao);
            $stmt->bindParam(':tipo_ent', $tipo_ent);
            $stmt->bindParam(':valor', $valor);
            $stmt->bindParam(':data', $data_entrada);
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':email', $email_logado);
            $stmt->execute();
        }

        $redirects = [
            'compras'    => 'listar_compras.php',
            'estoque'    => 'listar_estoque.php',
            'financeiro' => 'financeiro.php'
        ];
        header("Location: " . ($redirects[$tipo] ?? 'index.php'));
        exit();

    } catch (PDOException $e) {
        die("Erro ao atualizar: " . $e->getMessage());
    }
}
?>