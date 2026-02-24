<?php 
require_once 'config.php';
include 'session.php'; // Garante que só usuários logados acessem

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Novo Lembrete</title>
</head>
<body>
    <h2>Novo Lembrete</h2>
    
    <form method="POST" action="salvar_compromisso.php">
        <label for="descricao">Descrição:</label><br>
        <input type="text" id="descricao" name="descricao" placeholder="O que você precisa lembrar?" required><br><br>
        
        <label for="data">Data:</label><br>
        <input type="date" id="data" name="data" value="<?php echo date('Y-m-d'); ?>" required><br><br>
        
        <label for="hora">Hora:</label><br>
        <input type="time" id="hora" name="hora"><br><br>
        
        <input type="submit" value="Salvar Lembrete">
    </form>

    <p><a href="listar_compromissos.php">Voltar para a Lista</a></p>
    <hr/>
    <p><a href="index.php">Início</a></p> 
</body>
</html>