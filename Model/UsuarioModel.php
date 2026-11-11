<?php

class UsuarioModel {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function buscarTodos() {
        $stmt = $this->pdo->query("SELECT * FROM usuarios");
        return $stmt->fetchALL(PDO::FETCH_ASSOC);
    }

    public function buscarUsuario($id) {
        $stmt = $this->pdo->query("SELECT * FROM usuarios WHERE id = $id");
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function cadastrar($nome, $email, $telefone, $turma, $rm, $senha) {
        $sql = "INSERT INTO usuarios (nome, email, telefone, turma, rm, senha) VALUES (:nome, :email, :telefone, :turma, :rm, :senha)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':nome' => $nome,
            ':email' => $email,
            ':telefone' => $telefone,
            ':turma' => $turma,
            ':rm' => $rm,
            ':senha' => $senha
        ]);
    }

    public function editar($nome, $email, $telefone, $turma, $rm, $senha, $id) {
        $sql = "UPDATE usuarios SET nome=?, email=?, telefone=?, turma=?, rm=?, senha=? WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$nome, $email, $telefone, $turma, $rm, $senha, $id]);
    }

    public function deletar($id) {
        $sql = "DELETE FROM usuarios WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$id]);
    }

    public function login($rm, $senha) {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM usuarios Where rm = ?"
        );

        $stmt->execute([$rm]);

        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuario && $senha === $usuario['senha']) {
            $_SESSION['usuarioid'] = $usuario['id'];
            $_SESSION['nome'] = $usuario['nome'];

            header("Location: home.php");
            exit;
        } else {
            echo "RM ou senha incorretos.";
        }
        exit;
    }
}
?>