<?php

// Conexão com o banco de dados (ajuste essas variáveis)
$host = 'ip-45-79-13-239.cloudezapp.io';
$db   = 'mazal';
$user = 'mazal';
$pass = 'Rodrigo2012@';
$charset = 'utf8mb4';

$pdo = new PDO("mysql:host=$host;dbname=$db;charset=$charset", $user, $pass);


if(isset($_POST['user_id']) && isset($_POST['new_title'])) {
    $userId = $_POST['user_id'];
    $newTitle = $_POST['new_title'];

    // Atualiza o título na tabela home_title
    $query = "UPDATE `home_title` SET `title` = ? WHERE `user_id` = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$newTitle, $userId]);

    echo "Título atualizado com sucesso!";
} else {
    echo "Erro: Dados insuficientes fornecidos!";
}

?>
