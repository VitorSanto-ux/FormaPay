<?php
require_once "C:/Turma2/xampp/htdocs/FormaPay/Model/UsuarioModel.php";

class UsuarioController {
    private $usuarioModel;

    public function __construct($pdo) {
        $this->usuarioModel = new UsuarioModel($pdo);
    }

    public function listar() {
        $usuarios = $this->usuarioModel->buscarTodos();
        return $usuarios;
    }

    
}
?>