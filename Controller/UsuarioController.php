<?php
declare(strict_types=1);
require_once __DIR__ . '/../Model/UsuarioModel.php';

class UsuarioController
{
    public function __construct(private UsuarioModel $usuarioModel) {}

    public function cadastrar(string $nome, string $email, string $senha, string $perfil, ?string $foto=null): int
    {
        if (mb_strlen(trim($nome)) < 2) throw new InvalidArgumentException('Informe seu nome completo.');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) throw new InvalidArgumentException('Informe um e-mail válido.');
        if (strlen($senha) < 6) throw new InvalidArgumentException('A senha deve ter pelo menos 6 caracteres.');
        if (!in_array($perfil, ['comissao', 'participante'], true)) throw new InvalidArgumentException('Selecione comissão ou participante.');
        if ($this->usuarioModel->emailExiste($email)) throw new InvalidArgumentException('Este e-mail já está cadastrado.');
        return $this->usuarioModel->cadastrar(trim($nome), trim($email), $senha, $perfil, $foto);
    }

    public function login(string $email, string $senha): ?array
    {
        return $this->usuarioModel->autenticar(trim($email), $senha);
    }
    public function atualizarPerfil(int $id,string $nome,string $email,?string $foto=null): void
    { if(mb_strlen(trim($nome))<2||!filter_var($email,FILTER_VALIDATE_EMAIL))throw new InvalidArgumentException('Confira seu nome e e-mail.');$this->usuarioModel->atualizarPerfil($id,trim($nome),trim($email),$foto); }
    public function porId(int $id): ?array { return $this->usuarioModel->porId($id); }

    public function listar(): array { return $this->usuarioModel->buscarTodos(); }
    public function buscarUsuario(int $id): ?array { return $this->usuarioModel->buscarUsuario($id); }
    public function editar(int $id, string $nome, string $email): bool
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) throw new InvalidArgumentException('Informe um e-mail válido.');
        return $this->usuarioModel->editar($id, trim($nome), trim($email));
    }
    public function deletar(int $id): bool { return $this->usuarioModel->deletar($id); }
}
