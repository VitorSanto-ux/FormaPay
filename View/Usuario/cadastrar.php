<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastrar Usuário</title>
</head>
<body>
    <img src="" alt="">
    <form method="post">
        <label for="nome">Nome: </label>
        <input type="text" name="nome" required><br>

        <label for="email">Email: </label>
        <input type="email" name="email" required><br>

        <label for="telefone">Telefone: </label>
        <input type="text" name="telefone" required><br>

        <label for="senha">Senha: </label>
        <input type="password" name="senha" required><br>

        <label for="turma">Turma: </label>
        <select name="turma" required>
            <option value="">Selecione uma turma</option>
            <option value="9º Ano">9º Ano</option>
            <option value="3º Ano">3º Ano</option>
        </select>

        <label for="rm">RM: </label>
        <input type="number" name="rm" required><br>

        <input type="submit" value="Cadastrar">
        <button type="button" onclick="window.location.href='../../index.php'">Voltar ao Início</button>
    </form>
</body>
</html>

<?php
require_once "C:/Turma2/xampp/htdocs/FormaPay/DB/Database.php";
require_once "C:/Turma2/xampp/htdocs/FormaPay/Controller/UsuarioController.php";

$UsuarioController = new UsuarioController($pdo);

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $telefone = $_POST['telefone'];
    $senha = $_POST['senha'];
    $turma = $_POST['turma'];
    $rm = $_POST['rm'];

    $UsuarioController->cadastrar($nome, $email, $senha, $telefone, $turma, $rm);
    header("Location: ../../index.php");
}
?>