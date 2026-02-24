<?php
require_once 'config.php';
session_start();

// Inicializamos as variáveis para evitar erros de "undefined variable"
$entradas = 0;
$gastos = 0;
$saldo = 0;
$situacao = "";
$conselho = "";
$foto_perfil = "";

// Só processamos os cálculos se o usuário estiver de fato logado
if (isset($_SESSION['user_email'])) {
    $data_inicio = date('Y-m-01');
    $data_fim    = date('Y-m-t');
    $email_logado = $_SESSION['user_email'];
    $foto_perfil = $_SESSION['user_picture'] ?? '';

    try {
        // Entradas
        $sql_ent = "SELECT SUM(valor) FROM financeiro WHERE usuario_email = :email AND data_entrada BETWEEN :inicio AND :fim";
        $stmt_ent = $pdo->prepare($sql_ent);
        $stmt_ent->execute([':email' => $email_logado, ':inicio' => $data_inicio, ':fim' => $data_fim]);
        $entradas = $stmt_ent->fetchColumn() ?: 0;

        // Vendas realizadas (Estoque) - precisa JOIN para pegar valor do produto
        $sql_vendas = "SELECT v.quantidade_vendida, e.valor FROM vendas_estoque v JOIN estoque e ON v.id_estoque = e.id_estoque WHERE v.usuario_email = :email AND v.data_venda BETWEEN :inicio AND :fim";
        $stmt_vendas = $pdo->prepare($sql_vendas);
        $stmt_vendas->execute([':email' => $email_logado, ':inicio' => $data_inicio, ':fim' => $data_fim]);
        $total_vendas_estoque = 0;
        while ($v = $stmt_vendas->fetch(PDO::FETCH_ASSOC)) {
            $total_vendas_estoque += ($v['valor'] * $v['quantidade_vendida']);
        }

        // Gastos concluídos
        $sql_gas = "SELECT SUM(valor * quantidade) FROM compras WHERE concluido = 1 AND usuario_email = :email AND data_conclusao BETWEEN :inicio AND :fim";
        $stmt_gas = $pdo->prepare($sql_gas);
        $stmt_gas->execute([':email' => $email_logado, ':inicio' => $data_inicio, ':fim' => $data_fim]);
        $gastos = $stmt_gas->fetchColumn() ?: 0;

        // Projeção Final (igual financeiro.php)
        $saldo = ($entradas + $total_vendas_estoque) - $gastos;

        // 3. Lógica do Assistente (O "Secretário")
        if ($saldo > 0) {
            $situacao = "está <strong style='color:green'>POSITIVO</strong> em R$ " . number_format($saldo, 2, ',', '.');
            $conselho = "Ótimo trabalho! O mês está controlado.";
        } elseif ($saldo < 0) {
            $situacao = "está <strong style='color:red'>NEGATIVO</strong> em R$ " . number_format(abs($saldo), 2, ',', '.');
            $conselho = "Atenção! Seus gastos superaram as entradas este mês.";
        } else {
            $situacao = "está <strong style='color:orange'>ZERADO</strong>";
            $conselho = "Tudo em ordem, mas não temos margem de sobra hoje.";
        }
    } catch (PDOException $e) {
        $situacao = "Indisponível (Erro no banco)";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NOTE - Dashboard</title>
    <script src="https://accounts.google.com/gsi/client" async defer></script>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f0f2f5; text-align: center; padding: 30px; color: #333; }
        .dashboard-box { background: white; padding: 30px; border-radius: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); max-width: 500px; margin: auto; }
        .title { font-size: 2.5em; letter-spacing: 5px; color: #2c3e50; margin-bottom: 30px; }
        
        .profile-img { width: 80px; height: 80px; border-radius: 50%; border: 3px solid #3498db; margin-bottom: 15px; }
        
        .menu-list { list-style: none; padding: 0; display: flex; justify-content: center; gap: 15px; flex-wrap: wrap; margin-top: 25px; }
        .menu-list li a { text-decoration: none; background: #34495e; color: white; padding: 12px 25px; border-radius: 8px; font-weight: bold; font-size: 14px; transition: all 0.3s ease; display: inline-block; }
        .menu-list li a:hover { background: #2c3e50; transform: translateY(-3px); box-shadow: 0 5px 15px rgba(0,0,0,0.2); }
        
        .secretario-msg { background: #e8f4fd; border-left: 6px solid #3498db; padding: 20px; margin: 25px 0; text-align: left; border-radius: 4px; }
        .logout-btn { display: inline-block; margin-top: 20px; color: #95a5a6; font-size: 13px; text-decoration: none; transition: 0.3s; }
        .logout-btn:hover { color: #e74c3c; }
        
        /* Centralizar botão do Google */
        .g_id_signin { display: flex; justify-content: center; margin-top: 20px; }
    </style>
</head>
<body>

    <header>
        <h1 class="title">NOTE</h1>
    </header>

    <div class="dashboard-box">
        <?php if (!isset($_SESSION['user_email'])): ?>
            <h3 style="color: #7f8c8d;">Bem-vindo. Identifique-se para acessar o sistema:</h3>
            
            <div id="g_id_onload"
                 data-client_id="388951745160-b28lbnpnd4nultsf4dd2bie8h7s8qund.apps.googleusercontent.com"
                 data-context="signin"
                 data-ux_mode="popup"
                 data-callback="handleCredentialResponse"
                 data-auto_prompt="false">
            </div>

            <div class="g_id_signin" 
                 data-type="standard" 
                 data-shape="pill" 
                 data-theme="filled_blue" 
                 data-text="signin_with" 
                 data-size="large" 
                 data-logo_alignment="left">
            </div>
            <?php else: ?>
            <?php if ($foto_perfil): ?>
                <img src="<?php echo $foto_perfil; ?>" alt="Perfil" class="profile-img">
            <?php endif; ?>

            <h2 style="margin-top: 0;">Olá, <?php echo htmlspecialchars($_SESSION['user_name']); ?>! 👋</h2>
            
            <div class="secretario-msg">
                <span style="font-size: 1.2em;">🤖</span> <strong>Assistente Financeiro:</strong><br>
                <p style="margin: 10px 0 5px 0;">Seu saldo para este mês <?php echo $situacao; ?>.</p>
                <small style="color: #7f8c8d;"><?php echo $conselho; ?></small>
            </div>

            <ul class="menu-list">
                <li><a href="financeiro.php">FINANCEIRO</a></li>
                <li><a href="listar_estoque.php">ESTOQUE</a></li>
                <li><a href="listar_compras.php">COMPRAS</a></li>
                <li><a href="listar_compromissos.php">LEMBRETES</a></li>
            </ul>
            
            <a href="logout.php" class="logout-btn">Sair com segurança</a>
        <?php endif; ?>
    </div>

    <script>
        function handleCredentialResponse(response) {
            // Enviamos o token JWT retornado pelo Google para o seu script de login
            window.location.href = "login_google.php?token=" + response.credential;
        }
    </script>
</body>
</html>