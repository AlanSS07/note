<?php
// Impede que o arquivo seja acessado diretamente pela URL
if (basename($_SERVER['SCRIPT_FILENAME']) == 'config.php') {
    exit('Acesso direto não permitido');
}

// Carrega variáveis do arquivo .env
function loadEnv($filePath) {
    if (!file_exists($filePath)) {
        die("Arquivo .env não encontrado. Copie .env.example para .env e configure suas credenciais.");
    }
    
    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            $_ENV[$key] = $value;
        }
    }
}

loadEnv(__DIR__ . '/.env');

$host = $_ENV['DB_HOST'] ?? '';
$dbname = $_ENV['DB_NAME'] ?? '';
$username = $_ENV['DB_USER'] ?? '';
$password = $_ENV['DB_PASSWORD'] ?? '';
$port = $_ENV['DB_PORT'] ?? '';

if (!$host || !$dbname || !$username) {
    die("Erro: Variáveis de ambiente do banco de dados não estão configuradas em .env");
}

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8";
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro na conexão com o banco de dados: " . $e->getMessage());
}
?>