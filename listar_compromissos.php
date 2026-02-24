<?php 
require_once 'config.php';
include 'session.php'; // Já contém session_start() e verifica login

$email_logado = $_SESSION['user_email'];

echo "<h2>Lista de Lembretes:</h2>";

try {
    // ADEQUAÇÃO: Filtro por usuario_email para garantir privacidade
    $stmt = $pdo->prepare("SELECT id_compromissos, descricao, DATE_FORMAT(data, '%d/%m/%Y') AS data_formatada, 
                                  TIME_FORMAT(hora, '%H:%i') AS hora_formatada, concluido 
                           FROM compromissos 
                           WHERE usuario_email = :email 
                           ORDER BY concluido ASC, data ASC, hora ASC");
    $stmt->execute([':email' => $email_logado]);

    echo "<ul style='list-style: none; padding: 0;'>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $concluido = $row['concluido'];
        
        // Estilo visual para diferenciar concluídos de pendentes
        $estilo_texto = $concluido ? 'text-decoration: line-through; color: #7f8c8d;' : 'font-weight: bold;';
        $status_cor = $concluido ? 'green' : 'red';
        $status_texto = $concluido ? '[Concluído]' : '[Pendente]';

        echo "<li style='margin-bottom: 15px; border-bottom: 1px solid #eee; padding-bottom: 10px;'>";
        echo "<span style='$estilo_texto'>" . htmlspecialchars($row['descricao']) . "</span><br>";
        echo "<small>🗓️ " . $row['data_formatada'] . " às " . ($row['hora_formatada'] ?: '--:--') . "</small> ";
        echo "<b style='color: $status_cor;'>$status_texto</b>";

        echo "<br><div style='margin-top: 5px;'>";
        echo "<a href='editar_compromissos.php?id=" . $row['id_compromissos'] . "'>Editar</a>";

        if ($concluido) {
            echo " | <a href='marcar_concluido.php?tipo=compromissos&id=" . $row['id_compromissos'] . "&action=desmarcar' style='color: orange;'>Reabrir</a>";
        } else {
             echo " | <a href='marcar_concluido.php?tipo=compromissos&id=" . $row['id_compromissos'] . "&action=marcar' style='color: blue;'>Concluir</a>";
        }
        
        echo " | <a href='deletar.php?tipo=compromissos&id=" . $row['id_compromissos'] . "' 
               style='color: red;' onclick=\"return confirm('Deseja excluir este lembrete?')\">Deletar</a>";
        echo "</div></li>";
    }
    echo "</ul>";

    if ($stmt->rowCount() == 0) {
        echo "<p>Você não tem lembretes cadastrados.</p>";
    }

} catch (PDOException $e) {
    echo "Erro ao listar compromissos: " . $e->getMessage();
}
?>



<p>
    <a href="adicionar_compromisso.php"><b>+ Novo Lembrete</b></a> | 
    <a href="index.php">Voltar ao Início</a>
</p>