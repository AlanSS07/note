<?php
session_start();
session_unset(); // Limpa as variáveis
session_destroy(); // Destrói a sessão
header("Location: index.php");
exit();
?>