<?php
declare(strict_types=1);

namespace FormaPay\Controller;

use FormaPay\Model\FinanceiroModel;
use FormaPay\Model\UsuarioModel;

class FinanceiroController
{
    private FinanceiroModel $financeiroModel;
    private UsuarioModel $usuarioModel;

    public function __construct(?FinanceiroModel $financeiroModel = null, ?UsuarioModel $usuarioModel = null)
    {
        $this->financeiroModel = $financeiroModel ?? new FinanceiroModel();
        $this->usuarioModel = $usuarioModel ?? new UsuarioModel();
    }

    public function criarNovaCoorteComissao(
        int $usuarioId,
        string $nomeTurma,
        string $metaBruta,
        string $vencimentoDiaBruto = '10',
        ?string $instituicaoCurso = null,
        ?string $anoFormaturaBruto = null
    ): array {
        $nomeLimpo = trim($nomeTurma);
        if (mb_strlen($nomeLimpo) < 3) {
            return ['ok' => false, 'erro' => 'Informe o nome da turma dos formandos (mínimo de 3 caracteres).'];
        }

        $meta = $this->parseValorMonetario($metaBruta);
        if ($meta === null || $meta <= 0) {
            return ['ok' => false, 'erro' => 'Informe uma meta financeira válida maior que R$ 0,00.'];
        }

        $diaVenc = filter_var(trim($vencimentoDiaBruto), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 28]]);
        $diaVenc = $diaVenc === false ? 10 : (int) $diaVenc;

        $anoFormatura = null;
        if ($anoFormaturaBruto !== null && trim($anoFormaturaBruto) !== '') {
            $ano = filter_var(trim($anoFormaturaBruto), FILTER_VALIDATE_INT, ['options' => ['min_range' => 2020, 'max_range' => 2050]]);
            if ($ano === false) {
                return ['ok' => false, 'erro' => 'Ano de formatura inválido (use entre 2020 e 2050).'];
            }
            $anoFormatura = (int) $ano;
        }

        $coorte = $this->financeiroModel->criarCoorte(
            $usuarioId,
            $nomeLimpo,
            $meta,
            $diaVenc,
            $instituicaoCurso,
            $anoFormatura
        );

        $_SESSION['coorte_id'] = (int) $coorte['id'];

        return [
            'ok' => true,
            'coorte' => $coorte,
            'mensagem' => 'Turma "' . $coorte['nome'] . '" criada! Código de convite: ' . $coorte['codigo_convite'],
        ];
    }

    public function trocarCoorteAtivaComissao(int $usuarioId, int $coorteId): array
    {
        if (!$this->financeiroModel->usuarioEhComissaoDaCoorte($usuarioId, $coorteId)) {
            return ['ok' => false, 'erro' => 'Você não tem permissão para acessar esta turma.'];
        }

        $this->usuarioModel->definirCoorteAtiva($usuarioId, $coorteId);
        $_SESSION['coorte_id'] = $coorteId;

        return ['ok' => true, 'mensagem' => 'Turma ativa alterada.'];
    }

    public function salvarConfiguracoesCoorte(
        int $usuarioId,
        int $coorteId,
        string $nomeTurma,
        string $metaBruta,
        string $vencimentoDiaBruto,
        ?string $instituicaoCurso,
        ?string $anoFormaturaBruto,
        bool $ativa
    ): array {
        if (!$this->financeiroModel->usuarioEhComissaoDaCoorte($usuarioId, $coorteId)) {
            return ['ok' => false, 'erro' => 'Acesso negado para editar esta turma.'];
        }

        $nomeLimpo = trim($nomeTurma);
        if (mb_strlen($nomeLimpo) < 3) {
            return ['ok' => false, 'erro' => 'O nome da turma deve ter pelo menos 3 caracteres.'];
        }

        $meta = $this->parseValorMonetario($metaBruta);
        if ($meta === null || $meta <= 0) {
            return ['ok' => false, 'erro' => 'Informe uma meta financeira válida maior que zero.'];
        }

        $diaVenc = filter_var(trim($vencimentoDiaBruto), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 28]]);
        if ($diaVenc === false) {
            return ['ok' => false, 'erro' => 'O dia de vencimento deve estar entre 1 e 28.'];
        }

        $anoFormatura = null;
        if ($anoFormaturaBruto !== null && trim($anoFormaturaBruto) !== '') {
            $ano = filter_var(trim($anoFormaturaBruto), FILTER_VALIDATE_INT, ['options' => ['min_range' => 2020, 'max_range' => 2050]]);
            if ($ano === false) {
                return ['ok' => false, 'erro' => 'Ano de formatura inválido.'];
            }
            $anoFormatura = (int) $ano;
        }

        $this->financeiroModel->atualizarCoorte($coorteId, $nomeLimpo, $meta, (int) $diaVenc, $instituicaoCurso, $anoFormatura, $ativa);
        return ['ok' => true, 'mensagem' => 'Dados e meta da turma atualizados com sucesso.'];
    }

    public function regenerarCodigoCoorte(int $usuarioId, int $coorteId): array
    {
        if (!$this->financeiroModel->usuarioEhComissaoDaCoorte($usuarioId, $coorteId)) {
            return ['ok' => false, 'erro' => 'Acesso negado.'];
        }

        $novoCodigo = $this->financeiroModel->regenerarCodigoConvite($coorteId);
        return ['ok' => true, 'codigo' => $novoCodigo, 'mensagem' => 'Novo código gerado: ' . $novoCodigo];
    }

    public function adicionarMovimentacao(
        int $usuarioId,
        int $coorteId,
        string $tipo,
        ?int $categoriaId,
        string $descricao,
        string $valorBruto,
        string $dataMovimento,
        string $meio = 'pix',
        ?int $participanteId = null
    ): array {
        if (!$this->financeiroModel->usuarioEhComissaoDaCoorte($usuarioId, $coorteId)) {
            return ['ok' => false, 'erro' => 'Acesso negado para registrar movimentação nesta turma.'];
        }

        if (!in_array($tipo, ['receita', 'despesa'], true)) {
            return ['ok' => false, 'erro' => 'Tipo inválido. Use receita ou despesa.'];
        }

        $descricaoLimpa = trim($descricao);
        if (mb_strlen($descricaoLimpa) < 2) {
            return ['ok' => false, 'erro' => 'Informe uma descrição para a movimentação.'];
        }

        $valor = $this->parseValorMonetario($valorBruto);
        if ($valor === null || $valor <= 0) {
            return ['ok' => false, 'erro' => 'Informe um valor válido maior que R$ 0,00.'];
        }

        $dt = \DateTimeImmutable::createFromFormat('Y-m-d', trim($dataMovimento));
        if ($dt === false || $dt->format('Y-m-d') !== trim($dataMovimento)) {
            return ['ok' => false, 'erro' => 'Data do movimento inválida.'];
        }

        if ($participanteId !== null) {
            $participantes = $this->usuarioModel->listarParticipantesDaCoorte($coorteId);
            $idsValidos = array_map(static fn(array $p): int => (int) $p['participante_id'], $participantes);
            if (!in_array($participanteId, $idsValidos, true)) {
                return ['ok' => false, 'erro' => 'O participante selecionado não pertence a esta turma.'];
            }
        }

        $this->financeiroModel->registrarMovimentacao(
            $coorteId,
            $usuarioId,
            $tipo,
            $categoriaId,
            $descricaoLimpa,
            $valor,
            $dt->format('Y-m-d'),
            $meio,
            $participanteId
        );

        return ['ok' => true, 'mensagem' => 'Movimentação registrada com sucesso.'];
    }

    public function estornarMovimentacaoCoorte(int $usuarioId, int $coorteId, int $movimentacaoId): array
    {
        if (!$this->financeiroModel->usuarioEhComissaoDaCoorte($usuarioId, $coorteId)) {
            return ['ok' => false, 'erro' => 'Acesso negado.'];
        }

        $ok = $this->financeiroModel->estornarMovimentacao($coorteId, $usuarioId, $movimentacaoId);
        if (!$ok) {
            return ['ok' => false, 'erro' => 'Não foi possível estornar este lançamento (já estornado ou inexistente).'];
        }

        return ['ok' => true, 'mensagem' => 'Estorno registrado com trilha de auditoria.'];
    }

    public function validarCodigoPrevia(string $codigoConvite): array
    {
        $codigoLimpo = strtoupper(trim($codigoConvite));
        if ($codigoLimpo === '') {
            return ['ok' => false, 'erro' => 'Informe o código da turma fornecido pela comissão.'];
        }

        $coorte = $this->financeiroModel->buscarCoortePorCodigo($codigoLimpo);
        if ($coorte === null) {
            return ['ok' => false, 'erro' => 'Código não encontrado. Confira com a comissão da sua formatura.'];
        }

        if ((int) $coorte['ativa'] !== 1) {
            return ['ok' => false, 'erro' => 'Esta turma está inativa no momento.'];
        }

        return ['ok' => true, 'coorte' => $coorte];
    }

    public function entrarNaCoorteParticipante(int $usuarioId, string $nomeUsuario, string $emailUsuario, string $codigoConvite): array
    {
        $res = $this->financeiroModel->vincularParticipantePorCodigo($usuarioId, $nomeUsuario, $emailUsuario, $codigoConvite);
        if (!$res['ok']) {
            return $res;
        }

        $participante = $this->financeiroModel->obterParticipantePorUsuario($usuarioId);
        $_SESSION['coorte_id'] = (int) $res['coorte']['id'];
        $_SESSION['participante_id'] = $participante ? (int) $participante['participante_id'] : null;

        return [
            'ok' => true,
            'coorte' => $res['coorte'],
            'mensagem' => 'Bem-vindo à turma "' . $res['coorte']['nome'] . '"!',
        ];
    }

    public function parseValorMonetario(string $entrada): ?float
    {
        $limpo = trim($entrada);
        $limpo = preg_replace('/[^\d,\.]/', '', $limpo) ?? '';
        if ($limpo === '') {
            return null;
        }

        if (str_contains($limpo, ',') && str_contains($limpo, '.')) {
            $limpo = str_replace('.', '', $limpo);
            $limpo = str_replace(',', '.', $limpo);
        } elseif (str_contains($limpo, ',')) {
            $limpo = str_replace(',', '.', $limpo);
        }

        if (!is_numeric($limpo)) {
            return null;
        }

        return round((float) $limpo, 2);
    }
}