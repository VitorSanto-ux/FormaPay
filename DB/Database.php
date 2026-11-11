<?php

$dns = "mysql:host=Localhost;dbname=formapay;charset=utf8";
$usuario = "root";
$senha = "";

try {
    $pdo = new PDO($dns, $usuario, $senha);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Erro: " . $e->getMessage();
}
?>