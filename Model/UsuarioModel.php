<?php
declare(strict_types=1);

class UsuarioModel
{
    public function __construct(private PDO $pdo) {}

    public function cadastrar(string $nome, string $email, string $senha, string $perfil, ?string $foto=null): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO usuarios (nome, email, senha, perfil, foto_perfil) VALUES (:nome, :email, :senha, :perfil, :foto)');
        $stmt->execute(['nome' => $nome, 'email' => mb_strtolower($email), 'senha' => password_hash($senha, PASSWORD_DEFAULT), 'perfil' => $perfil, 'foto'=>$foto]);
        $id=(int)$this->pdo->lastInsertId();
        if($perfil==='participante'){
            $coorte=(int)$this->pdo->query('SELECT id FROM coortes ORDER BY id LIMIT 1')->fetchColumn();
            if($coorte){
                $existing=$this->pdo->prepare('SELECT id FROM participantes WHERE coorte_id=? AND email=? LIMIT 1');$existing->execute([$coorte,mb_strtolower($email)]);$pid=(int)($existing->fetchColumn()?:0);
                if($pid){$this->pdo->prepare('UPDATE participantes SET usuario_id=?,nome=?,status=\'ativo\' WHERE id=?')->execute([$id,$nome,$pid]);}
                else{$this->pdo->prepare("INSERT INTO participantes (coorte_id,usuario_id,nome,email,status,entrou_em) VALUES (?,?,?,?,'ativo',CURDATE())")->execute([$coorte,$id,$nome,mb_strtolower($email)]);$pid=(int)$this->pdo->lastInsertId();}
                $plan=$this->pdo->prepare('SELECT id,valor FROM planos_contribuicao WHERE coorte_id=? AND ativo=1 ORDER BY id LIMIT 1');$plan->execute([$coorte]);$pl=$plan->fetch();
                if(!$pl){$this->pdo->prepare("INSERT INTO planos_contribuicao (coorte_id,valor,periodicidade,vigencia_inicio) VALUES (?,150,'mensal',CURDATE())")->execute([$coorte]);$pl=['id'=>(int)$this->pdo->lastInsertId(),'valor'=>150];}
                $due=$this->pdo->prepare('INSERT INTO parcelas_previstas (participante_id,plano_id,competencia,vencimento,valor) VALUES (?,?,?,?,?)');
                for($i=1;$i<=11;$i++){$d=(new DateTimeImmutable('first day of next month'))->modify('+'.($i-1).' months');$dueDate=$d->setDate((int)$d->format('Y'),(int)$d->format('m'),10);$due->execute([$pid,$pl['id'],$d->format('Y-m-d'),$dueDate->format('Y-m-d'),$pl['valor']]);}
            }
        }
        return $id;
    }

    public function autenticar(string $email, string $senha): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, nome, email, senha, perfil, criado_em FROM usuarios WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => mb_strtolower($email)]);
        $usuario = $stmt->fetch();
        if (!$usuario || !password_verify($senha, $usuario['senha'])) return null;
        unset($usuario['senha']);
        return $usuario;
    }

    public function buscarTodos(): array
    {
        return $this->pdo->query('SELECT id, nome, email, criado_em FROM usuarios ORDER BY nome')->fetchAll();
    }

    public function buscarUsuario(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, nome, email, criado_em FROM usuarios WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function editar(int $id, string $nome, string $email): bool
    {
        $stmt = $this->pdo->prepare('UPDATE usuarios SET nome = :nome, email = :email WHERE id = :id');
        return $stmt->execute(['nome' => $nome, 'email' => mb_strtolower($email), 'id' => $id]);
    }

    public function deletar(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM usuarios WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }

    public function emailExiste(string $email): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM usuarios WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => mb_strtolower($email)]);
        return (bool) $stmt->fetchColumn();
    }

    public function atualizarPerfil(int $id,string $nome,string $email,?string $foto=null): void
    {
        $sql='UPDATE usuarios SET nome=?,email=?'.($foto!==null?',foto_perfil=?':'').' WHERE id=?';$args=[$nome,mb_strtolower($email)];if($foto!==null)$args[]=$foto;$args[]=$id;$this->pdo->prepare($sql)->execute($args);
    }

    public function porId(int $id): ?array
    { $st=$this->pdo->prepare('SELECT id,nome,email,perfil,foto_perfil FROM usuarios WHERE id=?');$st->execute([$id]);return $st->fetch()?:null; }
}
