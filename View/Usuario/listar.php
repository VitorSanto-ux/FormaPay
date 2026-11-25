<?php
require_once "C:/Turma2/xampp/htdocs/FormaPay/DB/Database.php";
require_once "C:/Turma2/xampp/htdocs/FormaPay/Controller/UsuarioController.php";

$usuarioController = new UsuarioController($pdo);
$usuarios = $usuarioController->listarTodos();
?>