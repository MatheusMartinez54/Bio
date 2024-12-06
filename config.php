<?php
// config.php

// Configurações do banco de dados
$host = 'localhost';
$db   = 'bio'; // Nome do seu banco de dados
$user = 'root';           // Usuário do MySQL (padrão é 'root' no XAMPP)
$pass = '';               // Senha do MySQL (padrão é vazio no XAMPP)
$charset = 'utf8mb4';

// DSN (Data Source Name)
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

// Opções adicionais
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Exibe erros
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Retorna resultados como array associativo
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Desativa emulação de prepared statements
];

// Tentativa de conexão
try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // Em caso de erro, exibe a mensagem
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}
?>
