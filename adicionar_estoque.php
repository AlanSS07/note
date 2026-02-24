<?php 
require_once 'config.php';
include 'session.php'; // Já verifica login e inicia sessão

$email_logado = $_SESSION['user_email'];

// Gerar sugestões de locais
$locais_padrao = [];
for ($i = 1; $i <= 20; $i++) { 
    $locais_padrao[] = "Prateleira $i"; 
    $locais_padrao[] = "Caixa $i"; 
}

// ADEQUAÇÃO: Buscar locais apenas do usuário logado para o datalist
try {
    $stmt_locais = $pdo->prepare("SELECT DISTINCT prateleira FROM estoque WHERE usuario_email = :email AND prateleira IS NOT NULL AND prateleira != '' 
                                  UNION 
                                  SELECT DISTINCT caixa FROM estoque WHERE usuario_email = :email AND caixa IS NOT NULL AND caixa != ''");
    $stmt_locais->execute([':email' => $email_logado]);
    $locais_bd = $stmt_locais->fetchAll(PDO::FETCH_COLUMN);
    
    $todos_locais = array_unique(array_merge($locais_padrao, $locais_bd));
    sort($todos_locais);
} catch (PDOException $e) {
    $todos_locais = $locais_padrao; // Fallback caso dê erro
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Adicionar ao Estoque</title>
    <style>
        /* Força visualmente o uppercase enquanto o usuário digita */
        #produto, #marca, #modelo { text-transform: uppercase; }
    </style>
</head>
<body>

    <h2>Novo Item no Estoque</h2>

    <form action="salvar_estoque.php" method="POST">
        <label for="produto">Produto:</label><br>
        <input type="text" id="produto" name="produto" oninput="this.value = this.value.toUpperCase()" required><br><br>

        <label for="marca">Marca:</label><br>
        <input type="text" id="marca" name="marca" oninput="this.value = this.value.toUpperCase()"><br><br>

        <label for="modelo">Modelo:</label><br>
        <input type="text" id="modelo" name="modelo" oninput="this.value = this.value.toUpperCase()"><br><br>

        <label for="valor">Valor (R$):</label><br>
        <input type="number" id="valor" name="valor" step="0.01" min="0" required><br><br>

        <label for="quantidade">Quantidade:</label><br>
        <input type="number" id="quantidade" name="quantidade" min="1" required><br><br>

        <label for="prateleira">Prateleira:</label><br>
        <input type="text" id="prateleira" name="prateleira" placeholder="Ex: 1 ou Prateleira 1"><br><br>

        <label for="caixa">Caixa:</label><br>
        <input type="text" id="caixa" name="caixa" placeholder="Ex: 5 ou Caixa 5"><br><br>

        <label for="gaveta">Gaveta:</label><br>
        <input type="text" id="gaveta" name="gaveta" placeholder="Ex: 2 ou Gaveta 2"><br><br>

        <input type="submit" value="Salvar no Estoque">
    </form>

    <br>
    <a href="listar_estoque.php">Voltar para a Lista</a>
    <br/><hr/><p><a href="index.php">Início</a></p>
</body>
</html>