<?php
require_once 'config.php';

echo "<h2>Lista de Compras:</h2>";

try {
        $stmt = $pdo->query("SELECT id_compra, produto, quantidade, concluido FROM compras");
    echo "<ul>";
     while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $concluido = $row['concluido']; 
        $status_texto = $concluido ? '[Concluído]' : '[Pendente]';
     
        echo "<li>" . htmlspecialchars($row['produto']) . " (Quantidade: " . $row['quantidade'] . ") " . $status_texto;

        echo " <a href='editar_compras.php?id=" . $row['id_compra'] . "'>Editar</a>";

        if ($concluido) {
            echo " | <a href='marcar_concluido.php?tipo=compras&id=" . $row['id_compra'] . "&action=desmarcar'>Desmarcar Concluído</a>";
        } else {
             echo " | <a href='marcar_concluido.php?tipo=compras&id=" . $row['id_compra'] . "&action=marcar'>Marcar como Concluído</a>";
        }
        echo " | <a href='deletar.php?tipo=compras&id=" . $row['id_compra'] . "'>Deletar</a></li>";
    }
    echo "</ul>";
} catch (PDOException $e) {
    echo "Erro ao listar compras: " . $e->getMessage();
}
?>

<p><a href="adicionar_compra.html">Nova Compra</a></p>

</br>
<hr/>
<p><a href="index.html">Inicio</a></p>