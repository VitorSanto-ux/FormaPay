<?php
declare(strict_types=1);

namespace FormaPay\Model;

use FormaPay\DB\Database;
use PDO;
use RuntimeException;

class FinanceiroModel
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    public function gerarCodigoConviteUnico(int $tamanho = 8): string
    {
        $alfabeto = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $max = strlen($alfabeto) - 1;

        for ($tentativa = 0; $tentativa < 20; $tentativa++) {
            $codigo = 'FP-';
            for ($i = 0; $i < $tamanho; $i++) {
                $codigo .= $alfabeto[random_int(0, $max)];
            }

            $stmt = $this->db->prepare('SELECT 1 FROM coortes WHERE codigo_convite = :codigo LIMIT 1');
            $stmt->execute([':codigo' => $codigo]);
            if (!$stmt->fetchColumn()) {
                return $codigo;
            }
        }

        throw new RuntimeException('Não foi possível gerar um código de convite único.');
    }

    public function criarCoorte(
        int $comissaoUsuarioId,
        string $nomeTurma,
        float $metaFinanceira,
        int $vencimentoDia = 10,
        ?string $instituicaoCurso = null,
        ?int $anoFormatura = null
    ): array {
        $this->db->beginTransaction();
        try {
            $codigoConvite = $this->gerarCodigoConviteUnico(8);
            $diaSeguro = max(1, min(28, $vencimentoDia));

            $sqlCoorte = 'INSERT INTO coortes
                            (nome, instituicao_curso, ano_formatura, meta, vencimento_dia, codigo_convite, ativa, criado_por)
                          VALUES
                            (:nome, :instituicao_curso, :ano_formatura, :meta, :vencimento_dia, :codigo_convite, 1, :criado_por)';
            $stmtCoorte = $this->db->prepare($sqlCoorte);
            $stmtCoorte->execute([
                ':nome' => trim($nomeTurma),
                ':instituicao_curso' => $instituicaoCurso !== '' ? trim((string) $instituicaoCurso) : null,
                ':ano_formatura' => $anoFormatura,
                ':meta' => number_format($metaFinanceira, 2, '.', ''),
                ':vencimento_dia' => $diaSeguro,
                ':codigo_convite' => $codigoConvite,
                ':criado_por' => $comissaoUsuarioId,
            ]);

            $coorteId = (int) $this->db->lastInsertId();

            $sqlComissao = 'INSERT INTO coorte_comissao (coorte_id, usuario_id, papel)
                            VALUES (:coorte_id, :usuario_id, :papel)';
            $stmtComissao = $this->db->prepare($sqlComissao);
            $stmtComissao->execute([
                ':coorte_id' => $coorteId,
                ':usuario_id' => $comissaoUsuarioId,
                ':papel' => 'presidente',
            ]);

            // Categorias padrão iniciais da coorte
            $categoriasPadrao = [
                ['Mensalidade', 'receita'],
                ['Evento / Rifa', 'receita'],
                ['Doação / Patrocínio', 'receita'],
                ['Buffet / Salão', 'despesa'],
                ['Decoração / Fotografia', 'despesa'],
                ['Despesas Administrativas', 'despesa'],
            ];
            $stmtCat = $this->db->prepare('INSERT INTO categorias_financeiras (coorte_id, nome, tipo) VALUES (:coorte_id, :nome, :tipo)');
            foreach ($categoriasPadrao as [$catNome, $catTipo]) {
                $stmtCat->execute([
                    ':coorte_id' => $coorteId,
                    ':nome' => $catNome,
                    ':tipo' => $catTipo,
                ]);
            }

            if ($anoFormatura !== null) {
                $stmtAno = $this->db->prepare('INSERT IGNORE INTO anos_letivos (coorte_id, ano_escolar, ano) VALUES (:coorte_id, 3, :ano)');
                $stmtAno->execute([
                    ':coorte_id' => $coorteId,
                    ':ano' => $anoFormatura,
                ]);
            }

            $stmtAtiva = $this->db->prepare('UPDATE usuarios SET coorte_ativa_id = :coorte_id WHERE id = :usuario_id');
            $stmtAtiva->execute([
                ':coorte_id' => $coorteId,
                ':usuario_id' => $comissaoUsuarioId,
            ]);

            $this->db->commit();

            return $this->buscarCoortePorId($coorteId) ?? [];
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function atualizarCoorte(
        int $coorteId,
        string $nomeTurma,
        float $metaFinanceira,
        int $vencimentoDia,
        ?string $instituicaoCurso = null,
        ?int $anoFormatura = null,
        bool $ativa = true
    ): bool {
        $sql = 'UPDATE coortes
                SET nome = :nome,
                    instituicao_curso = :instituicao_curso,
                    ano_formatura = :ano_formatura,
                    meta = :meta,
                    vencimento_dia = :vencimento_dia,
                    ativa = :ativa
                WHERE id = :id';
        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':id' => $coorteId,
            ':nome' => trim($nomeTurma),
            ':instituicao_curso' => $instituicaoCurso !== '' ? trim((string) $instituicaoCurso) : null,
            ':ano_formatura' => $anoFormatura,
            ':meta' => number_format($metaFinanceira, 2, '.', ''),
            ':vencimento_dia' => max(1, min(28, $vencimentoDia)),
            ':ativa' => $ativa ? 1 : 0,
        ]);
    }

    public function regenerarCodigoConvite(int $coorteId): string
    {
        $novoCodigo = $this->gerarCodigoConviteUnico(8);
        $stmt = $this->db->prepare('UPDATE coortes SET codigo_convite = :codigo WHERE id = :id');
        $stmt->execute([
            ':codigo' => $novoCodigo,
            ':id' => $coorteId,
        ]);

        return $novoCodigo;
    }

    public function buscarCoortePorId(int $coorteId): ?array
    {
        $sql = 'SELECT id, nome, instituicao_curso, ano_formatura, meta, vencimento_dia, codigo_convite, ativa, criado_por, criado_em
                FROM coortes
                WHERE id = :id
                LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $coorteId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function buscarCoortePorCodigo(string $codigoConvite): ?array
    {
        $sql = 'SELECT id, nome, instituicao_curso, ano_formatura, meta, vencimento_dia, codigo_convite, ativa
                FROM coortes
                WHERE UPPER(TRIM(codigo_convite)) = :codigo
                LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':codigo' => strtoupper(trim($codigoConvite))]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function listarCoortesDaComissao(int $usuarioId): array
    {
        $sql = 'SELECT DISTINCT c.id, c.nome, c.instituicao_curso, c.ano_formatura, c.meta, c.vencimento_dia, c.codigo_convite, c.ativa, c.criado_em
                FROM coortes c
                LEFT JOIN coorte_comissao cc ON cc.coorte_id = c.id AND cc.usuario_id = :usuario_id_cc
                WHERE cc.usuario_id IS NOT NULL OR c.criado_por = :usuario_id_criador
                ORDER BY c.criado_em DESC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':usuario_id_cc' => $usuarioId,
            ':usuario_id_criador' => $usuarioId,
        ]);

        return $stmt->fetchAll();
    }

    public function usuarioEhComissaoDaCoorte(int $usuarioId, int $coorteId): bool
    {
        if ($usuarioId <= 0 || $coorteId <= 0) {
            return false;
        }
        $sql = 'SELECT 1
                FROM coortes c
                LEFT JOIN coorte_comissao cc ON cc.coorte_id = c.id AND cc.usuario_id = :usuario_id_cc
                WHERE c.id = :coorte_id
                  AND (cc.usuario_id IS NOT NULL OR c.criado_por = :usuario_id_criador)
                LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':coorte_id' => $coorteId,
            ':usuario_id_cc' => $usuarioId,
            ':usuario_id_criador' => $usuarioId,
        ]);

        return (bool) $stmt->fetchColumn();
    }

    public function obterParticipantePorUsuario(int $usuarioId): ?array
    {
        $sql = 'SELECT p.id AS participante_id, p.coorte_id, p.usuario_id, p.nome AS participante_nome,
                       p.email AS participante_email, p.status AS participante_status, p.entrou_em,
                       c.nome AS coorte_nome, c.instituicao_curso, c.ano_formatura, c.meta,
                       c.vencimento_dia, c.codigo_convite, c.ativa
                FROM participantes p
                INNER JOIN coortes c ON c.id = p.coorte_id
                WHERE p.usuario_id = :usuario_id
                LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':usuario_id' => $usuarioId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function vincularParticipantePorCodigo(int $usuarioId, string $nomeUsuario, string $emailUsuario, string $codigoConvite): array
    {
        $coorte = $this->buscarCoortePorCodigo($codigoConvite);
        if ($coorte === null) {
            return ['ok' => false, 'erro' => 'Código de convite inválido ou inexistente.'];
        }

        if ((int) $coorte['ativa'] !== 1) {
            return ['ok' => false, 'erro' => 'Esta turma está inativa no momento.'];
        }

        $vinculoAtual = $this->obterParticipantePorUsuario($usuarioId);
        if ($vinculoAtual !== null) {
            if ((int) $vinculoAtual['coorte_id'] !== (int) $coorte['id']) {
                return [
                    'ok' => false,
                    'erro' => 'Você já pertence à turma "' . $vinculoAtual['coorte_nome'] . '". Não é permitido entrar em outra turma.',
                ];
            }
            if ($vinculoAtual['participante_status'] !== 'ativo') {
                return ['ok' => false, 'erro' => 'Seu vínculo nesta turma está marcado como ' . $vinculoAtual['participante_status'] . '. Fale com a comissão.'];
            }

            $this->atualizarCoorteAtivaUsuario($usuarioId, (int) $coorte['id']);
            return ['ok' => true, 'coorte' => $coorte];
        }

        $this->db->beginTransaction();
        try {
            $sql = 'INSERT INTO participantes (coorte_id, usuario_id, nome, email, status, entrou_em)
                    VALUES (:coorte_id, :usuario_id, :nome, :email, :status, :entrou_em)';
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':coorte_id' => (int) $coorte['id'],
                ':usuario_id' => $usuarioId,
                ':nome' => trim($nomeUsuario),
                ':email' => mb_strtolower(trim($emailUsuario)),
                ':status' => 'ativo',
                ':entrou_em' => date('Y-m-d'),
            ]);

            $this->atualizarCoorteAtivaUsuario($usuarioId, (int) $coorte['id']);
            $this->db->commit();

            return ['ok' => true, 'coorte' => $coorte];
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    private function atualizarCoorteAtivaUsuario(int $usuarioId, int $coorteId): void
    {
        $stmt = $this->db->prepare('UPDATE usuarios SET coorte_ativa_id = :coorte_id WHERE id = :usuario_id');
        $stmt->execute([
            ':coorte_id' => $coorteId,
            ':usuario_id' => $usuarioId,
        ]);
    }

    public function listarCategoriasDaCoorte(int $coorteId): array
    {
        $sql = 'SELECT id, coorte_id, nome, tipo
                FROM categorias_financeiras
                WHERE coorte_id = :coorte_id
                ORDER BY nome ASC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':coorte_id' => $coorteId]);

        return $stmt->fetchAll();
    }

    public function registrarMovimentacao(
        int $coorteId,
        int $usuarioId,
        string $tipo,
        ?int $categoriaId,
        string $descricao,
        float $valor,
        string $dataMovimento,
        string $meio = 'pix',
        ?int $participanteId = null,
        ?int $parcelaId = null
    ): int {
        $tipoSeguro = in_array($tipo, ['receita', 'despesa'], true) ? $tipo : 'receita';
        $meioSeguro = in_array($meio, ['pix', 'dinheiro', 'transferencia', 'cartao', 'outro'], true) ? $meio : 'pix';
        $competencia = substr($dataMovimento, 0, 7) . '-01';

        $this->db->beginTransaction();
        try {
            $sql = 'INSERT INTO movimentacoes
                        (coorte_id, parcela_id, participante_id, usuario_id, tipo, categoria_id, descricao, valor, data_movimento, competencia, meio)
                    VALUES
                        (:coorte_id, :parcela_id, :participante_id, :usuario_id, :tipo, :categoria_id, :descricao, :valor, :data_movimento, :competencia, :meio)';
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':coorte_id' => $coorteId,
                ':parcela_id' => $parcelaId,
                ':participante_id' => $participanteId,
                ':usuario_id' => $usuarioId,
                ':tipo' => $tipoSeguro,
                ':categoria_id' => $categoriaId,
                ':descricao' => trim($descricao),
                ':valor' => number_format($valor, 2, '.', ''),
                ':data_movimento' => $dataMovimento,
                ':competencia' => $competencia,
                ':meio' => $meioSeguro,
            ]);

            $movId = (int) $this->db->lastInsertId();

            $sqlHist = 'INSERT INTO historico_movimentacoes (movimentacao_id, usuario_id, acao, detalhes)
                        VALUES (:movimentacao_id, :usuario_id, :acao, :detalhes)';
            $stmtHist = $this->db->prepare($sqlHist);
            $stmtHist->execute([
                ':movimentacao_id' => $movId,
                ':usuario_id' => $usuarioId,
                ':acao' => 'criacao',
                ':detalhes' => json_encode([
                    'coorte_id' => $coorteId,
                    'tipo' => $tipoSeguro,
                    'valor' => round($valor, 2),
                    'meio' => $meioSeguro,
                ], JSON_UNESCAPED_UNICODE),
            ]);

            $this->db->commit();
            return $movId;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function estornarMovimentacao(int $coorteId, int $usuarioId, int $movimentacaoId): bool
    {
        $sqlBusca = 'SELECT id, coorte_id, parcela_id, participante_id, tipo, categoria_id, descricao, valor, data_movimento, competencia, meio, estorno_de
                     FROM movimentacoes
                     WHERE id = :id AND coorte_id = :coorte_id
                     LIMIT 1';
        $stmtBusca = $this->db->prepare($sqlBusca);
        $stmtBusca->execute([
            ':id' => $movimentacaoId,
            ':coorte_id' => $coorteId,
        ]);
        $orig = $stmtBusca->fetch();

        if (!$orig || $orig['estorno_de'] !== null) {
            return false;
        }

        // Verifica se já existe estorno
        $stmtCheck = $this->db->prepare('SELECT 1 FROM movimentacoes WHERE estorno_de = :id AND coorte_id = :coorte_id LIMIT 1');
        $stmtCheck->execute([':id' => $movimentacaoId, ':coorte_id' => $coorteId]);
        if ($stmtCheck->fetchColumn()) {
            return false;
        }

        $tipoInverso = $orig['tipo'] === 'receita' ? 'despesa' : 'receita';

        $this->db->beginTransaction();
        try {
            $sqlIns = 'INSERT INTO movimentacoes
                        (coorte_id, parcela_id, participante_id, usuario_id, tipo, categoria_id, descricao, valor, data_movimento, competencia, meio, estorno_de)
                       VALUES
                        (:coorte_id, :parcela_id, :participante_id, :usuario_id, :tipo, :categoria_id, :descricao, :valor, :data_movimento, :competencia, :meio, :estorno_de)';
            $stmtIns = $this->db->prepare($sqlIns);
            $stmtIns->execute([
                ':coorte_id' => $coorteId,
                ':parcela_id' => $orig['parcela_id'],
                ':participante_id' => $orig['participante_id'],
                ':usuario_id' => $usuarioId,
                ':tipo' => $tipoInverso,
                ':categoria_id' => $orig['categoria_id'],
                ':descricao' => 'Estorno: ' . $orig['descricao'],
                ':valor' => $orig['valor'],
                ':data_movimento' => date('Y-m-d'),
                ':competencia' => date('Y-m-01'),
                ':meio' => $orig['meio'],
                ':estorno_de' => $movimentacaoId,
            ]);

            $estornoId = (int) $this->db->lastInsertId();

            $stmtHist = $this->db->prepare(
                'INSERT INTO historico_movimentacoes (movimentacao_id, usuario_id, acao, detalhes)
                 VALUES (:mov_id, :usuario_id, :acao, :detalhes)'
            );
            $stmtHist->execute([
                ':mov_id' => $movimentacaoId,
                ':usuario_id' => $usuarioId,
                ':acao' => 'estorno',
                ':detalhes' => json_encode(['estorno_movimentacao_id' => $estornoId], JSON_UNESCAPED_UNICODE),
            ]);

            $this->db->commit();
            return true;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function listarMovimentacoes(int $coorteId, ?string $tipo = null, ?int $limite = null, ?int $participanteId = null): array
    {
        $sql = 'SELECT m.id, m.coorte_id, m.parcela_id, m.participante_id, m.usuario_id, m.tipo,
                       m.categoria_id, COALESCE(cf.nome, "Geral") AS categoria_nome,
                       m.descricao, m.valor, m.data_movimento, m.competencia, m.meio, m.estorno_de,
                       p.nome AS participante_nome,
                       EXISTS(SELECT 1 FROM movimentacoes e WHERE e.estorno_de = m.id) AS ja_estornado
                FROM movimentacoes m
                LEFT JOIN categorias_financeiras cf ON cf.id = m.categoria_id
                LEFT JOIN participantes p ON p.id = m.participante_id
                WHERE m.coorte_id = :coorte_id';
        $params = [':coorte_id' => $coorteId];

        if ($tipo !== null && in_array($tipo, ['receita', 'despesa'], true)) {
            $sql .= ' AND m.tipo = :tipo';
            $params[':tipo'] = $tipo;
        }

        if ($participanteId !== null && $participanteId > 0) {
            $sql .= ' AND m.participante_id = :participante_id';
            $params[':participante_id'] = $participanteId;
        }

        $sql .= ' ORDER BY m.data_movimento DESC, m.id DESC';

        if ($limite !== null && $limite > 0) {
            $sql .= ' LIMIT ' . (int) $limite;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function obterResumoFinanceiro(int $coorteId): array
    {
        $coorte = $this->buscarCoortePorId($coorteId);
        $meta = $coorte ? (float) $coorte['meta'] : 0.0;

        $sql = 'SELECT
                    COALESCE(SUM(CASE WHEN tipo = "receita" THEN valor ELSE 0 END), 0) AS total_receitas,
                    COALESCE(SUM(CASE WHEN tipo = "despesa" THEN valor ELSE 0 END), 0) AS total_despesas,
                    COUNT(*) AS total_lancamentos
                FROM movimentacoes
                WHERE coorte_id = :coorte_id';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':coorte_id' => $coorteId]);
        $totais = $stmt->fetch() ?: ['total_receitas' => 0, 'total_despesas' => 0, 'total_lancamentos' => 0];

        $receitas = (float) $totais['total_receitas'];
        $despesas = (float) $totais['total_despesas'];
        $saldo = round($receitas - $despesas, 2);
        $progresso = $meta > 0 ? round(min(100, max(0, ($saldo / $meta) * 100)), 1) : 0.0;
        $faltaMeta = round(max(0.0, $meta - $saldo), 2);

        $stmtPart = $this->db->prepare('SELECT COUNT(*) FROM participantes WHERE coorte_id = :coorte_id AND status = "ativo"');
        $stmtPart->execute([':coorte_id' => $coorteId]);
        $totalParticipantes = (int) $stmtPart->fetchColumn();

        return [
            'coorte' => $coorte,
            'meta' => $meta,
            'total_receitas' => $receitas,
            'total_despesas' => $despesas,
            'saldo_atual' => $saldo,
            'falta_meta' => $faltaMeta,
            'progresso_percentual' => $progresso,
            'total_lancamentos' => (int) $totais['total_lancamentos'],
            'total_participantes' => $totalParticipantes,
        ];
    }

    public function obterResumoMensal(int $coorteId, int $quantidadeMeses = 6): array
    {
        $quantidadeMeses = max(1, min(24, $quantidadeMeses));
        $inicioMesAtual = new \DateTimeImmutable('first day of this month');
        $dataInicio = $inicioMesAtual->modify('-' . ($quantidadeMeses - 1) . ' months')->format('Y-m-d');
        $dataFim = $inicioMesAtual->modify('+1 month')->format('Y-m-d');

        $sql = 'SELECT DATE_FORMAT(data_movimento, "%Y-%m") AS mes,
                       COALESCE(SUM(CASE WHEN tipo = "receita" THEN valor ELSE 0 END), 0) AS total_receitas,
                       COALESCE(SUM(CASE WHEN tipo = "despesa" THEN valor ELSE 0 END), 0) AS total_despesas
                FROM movimentacoes
                WHERE coorte_id = :coorte_id
                  AND data_movimento >= :data_inicio
                  AND data_movimento < :data_fim
                GROUP BY DATE_FORMAT(data_movimento, "%Y-%m")
                ORDER BY mes';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':coorte_id' => $coorteId,
            ':data_inicio' => $dataInicio,
            ':data_fim' => $dataFim,
        ]);

        return $stmt->fetchAll();
    }

    public function obterResumoPorCategoria(int $coorteId): array
    {
        $sql = 'SELECT COALESCE(cf.nome, "Geral") AS categoria, m.tipo, SUM(m.valor) AS total
                FROM movimentacoes m
                LEFT JOIN categorias_financeiras cf ON cf.id = m.categoria_id
                WHERE m.coorte_id = :coorte_id
                GROUP BY COALESCE(cf.nome, "Geral"), m.tipo
                ORDER BY total DESC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':coorte_id' => $coorteId]);

        return $stmt->fetchAll();
    }
}
