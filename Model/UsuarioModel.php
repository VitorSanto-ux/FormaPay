<?php
declare(strict_types=1);

namespace FormaPay\Model;

use FormaPay\DB\Database;
use PDO;
use RuntimeException;

class UsuarioModel
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    public function buscarPorEmail(string $email): ?array
    {
        $sql = 'SELECT id, nome, email, senha, perfil, foto_perfil, coorte_ativa_id, criado_em
                FROM usuarios
                WHERE email = :email
                LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':email' => mb_strtolower(trim($email))]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function buscarPorId(int $id): ?array
    {
        $sql = 'SELECT id, nome, email, senha, perfil, foto_perfil, coorte_ativa_id, criado_em
                FROM usuarios
                WHERE id = :id
                LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function cadastrar(string $nome, string $email, string $senhaPlana, string $perfil = 'participante'): int
    {
        $perfilSeguro = in_array($perfil, ['comissao', 'participante'], true) ? $perfil : 'participante';
        $hash = password_hash($senhaPlana, PASSWORD_DEFAULT);

        if ($hash === false) {
            throw new RuntimeException('Falha ao gerar hash seguro da senha.');
        }

        $sql = 'INSERT INTO usuarios (nome, email, senha, perfil, coorte_ativa_id)
                VALUES (:nome, :email, :senha, :perfil, NULL)';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':nome' => trim($nome),
            ':email' => mb_strtolower(trim($email)),
            ':senha' => $hash,
            ':perfil' => $perfilSeguro,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function atualizarPerfil(int $usuarioId, string $nome, string $email, ?string $novaSenha = null, ?string $fotoPerfil = null): bool
    {
        $campos = ['nome = :nome', 'email = :email'];
        $params = [
            ':id' => $usuarioId,
            ':nome' => trim($nome),
            ':email' => mb_strtolower(trim($email)),
        ];

        if ($novaSenha !== null && $novaSenha !== '') {
            $hash = password_hash($novaSenha, PASSWORD_DEFAULT);
            if ($hash === false) {
                throw new RuntimeException('Falha ao atualizar senha.');
            }
            $campos[] = 'senha = :senha';
            $params[':senha'] = $hash;
        }

        if ($fotoPerfil !== null) {
            $campos[] = 'foto_perfil = :foto_perfil';
            $params[':foto_perfil'] = $fotoPerfil;
        }

        $this->db->beginTransaction();
        try {
            $sql = 'UPDATE usuarios SET ' . implode(', ', $campos) . ' WHERE id = :id';
            $stmt = $this->db->prepare($sql);
            $ok = $stmt->execute($params);

            $sqlPart = 'UPDATE participantes SET nome = :nome, email = :email WHERE usuario_id = :usuario_id';
            $stmtPart = $this->db->prepare($sqlPart);
            $stmtPart->execute([
                ':nome' => trim($nome),
                ':email' => mb_strtolower(trim($email)),
                ':usuario_id' => $usuarioId,
            ]);

            $this->db->commit();
            return $ok;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function definirCoorteAtiva(int $usuarioId, ?int $coorteId): bool
    {
        $sql = 'UPDATE usuarios SET coorte_ativa_id = :coorte_id WHERE id = :usuario_id';
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
        if ($coorteId === null) {
            $stmt->bindValue(':coorte_id', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(':coorte_id', $coorteId, PDO::PARAM_INT);
        }

        return $stmt->execute();
    }

    public function listarParticipantesDaCoorte(int $coorteId): array
    {
        $sql = 'SELECT p.id AS participante_id, p.coorte_id, p.usuario_id, p.nome, p.email, p.status, p.entrou_em, p.saiu_em,
                       u.foto_perfil,
                       COALESCE(SUM(CASE WHEN m.tipo = "receita" AND m.estorno_de IS NULL THEN m.valor ELSE 0 END), 0) AS total_contribuido
                FROM participantes p
                LEFT JOIN usuarios u ON u.id = p.usuario_id
                LEFT JOIN movimentacoes m ON m.participante_id = p.id AND m.coorte_id = p.coorte_id AND m.excluida_em IS NULL
                WHERE p.coorte_id = :coorte_id
                GROUP BY p.id, p.coorte_id, p.usuario_id, p.nome, p.email, p.status, p.entrou_em, p.saiu_em, u.foto_perfil
                ORDER BY p.nome ASC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':coorte_id' => $coorteId]);

        return $stmt->fetchAll();
    }

    public function alterarStatusParticipante(int $coorteId, int $participanteId, string $status): bool
    {
        if (!in_array($status, ['ativo', 'inativo', 'desistente'], true)) {
            return false;
        }

        $saiuEm = $status === 'ativo' ? null : date('Y-m-d');
        $sql = 'UPDATE participantes
                SET status = :status,
                    saiu_em = :saiu_em
                WHERE id = :id
                  AND coorte_id = :coorte_id';
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':status', $status);
        if ($saiuEm === null) {
            $stmt->bindValue(':saiu_em', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(':saiu_em', $saiuEm);
        }
        $stmt->bindValue(':id', $participanteId, PDO::PARAM_INT);
        $stmt->bindValue(':coorte_id', $coorteId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }
}
