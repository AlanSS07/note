<?php 
require_once 'config.php';
include 'session.php'; // Já verifica login e inicia a sessão
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Registrar Entrada Financeira</title>
    <style>
        /* Mantém o padrão visual de letras maiúsculas enquanto digita */
        #descricao { text-transform: uppercase; }
    </style>
</head>
<body>

    <h2>Registrar Salário ou Serviço Prestado</h2>

    <form action="salvar_financeiro.php" method="POST">
        <label for="tipo">Tipo de Entrada:</label><br>
        <select name="tipo" id="tipo" required>
            <option value="Salário">Salário</option>
            <option value="Serviço">Serviço Prestado</option>
            <option value="Outros">Outros</option>
        </select><br><br>

        <label for="descricao">Descrição / Origem:</label><br>
        <input type="text" id="descricao" name="descricao" placeholder="Ex: SALÁRIO MENSAL" oninput="this.value = this.value.toUpperCase()" required><br><br>

        <label for="valor">Valor (R$):</label><br>
        <input type="number" id="valor" name="valor" step="0.01" min="0.01" required><br><br>

        <label for="data_entrada">Data do Recebimento:</label><br>
        <input type="date" id="data_entrada" name="data_entrada" value="<?php echo date('Y-m-d'); ?>" required><br><br>

        <input type="submit" value="Registrar Entrada">
    </form>

    <br>
    <a href="financeiro.php">Ver Painel Financeiro</a> | 
    <a href="index.php">Início</a>

</body>
</html>