<?php
require_once "C:/Turma2/xampp/htdocs/FormaPay/Model/UsuarioModel.php";

class UsuarioController {
    private $usuarioModel;

    public function __construct($pdo) {
        $this->usuarioModel = new UsuarioModel($pdo);
    }

    public function listar() {
        $usuarios = $this->usuarioModel->buscarTodos();
        include_once "";
        return;
    }

    public function buscarUsuario($id) {
        $usuario = $this->usuarioModel->buscarUsuario($id);
        return $usuario;
    }

    public function cadastrar($nome, $email, $telefone, $turma, $rm, $senha) {
        $this->usuarioModel->cadastrar($nome, $email, $telefone, $turma, $rm, $senha);
    }

    public function login($rm, $senha) {
        $this->usuarioModel->login($rm, $senha);
    }

    public function editar($nome, $email,$telefone, $turma, $rm, $senha, $id) {
        $this->usuarioModel->editar($nome, $email, $telefone, $turma, $rm, $senha, $id);
    }

    public function deletar($id) {
        $usuario = $this->usuarioModel->deletar($id);
        return $usuario;
    }
}
?>