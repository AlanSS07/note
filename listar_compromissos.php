<?php
require_once 'config.php';

echo "<h2>Lista de Lembretes:</h2>";

try {
    $stmt = $pdo->query("SELECT id_compromissos, descricao, DATE_FORMAT(data, '%d/%m/%Y') AS data_formatada, TIME_FORMAT(hora, '%H:%i') AS hora_formatada, concluido FROM compromissos ORDER BY data, hora");
    echo "<ul>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $concluido = $row['concluido'];
        $status_texto = $concluido ? '[Concluído]' : '[Pendente]';

        echo "<li>" . htmlspecialchars($row['descricao']) . " - " . $row['data_formatada'] . " às " . $row['hora_formatada'] . " " . $status_texto;

        echo " <a href='editar_compromissos.php?id=" . $row['id_compromissos'] . "'>Editar</a>";

        if ($concluido) {
            echo " | <a href='marcar_concluido.php?tipo=compromissos&id=" . $row['id_compromissos'] . "&action=desmarcar'>Desmarcar Concluído</a>";
        } else {
             echo " | <a href='marcar_concluido.php?tipo=compromissos&id=" . $row['id_compromissos'] . "&action=marcar'>Marcar como Concluído</a>";
        }
        echo " | <a href='deletar.php?tipo=compromissos&id=" . $row['id_compromissos'] . "'>Deletar</a></li>";
    }
    echo "</ul>";
} catch (PDOException $e) {
    echo "Erro ao listar compromissos: " . $e->getMessage();
}
?>

<p><a href="adicionar_compromisso.html">Novo Lembrete</a></p>

</br>
    <hr/>
    <p><a href="index.html">Inicio</a></p>