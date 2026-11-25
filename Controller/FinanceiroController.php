<?php
declare(strict_types=1);
require_once __DIR__ . '/../Model/FinanceiroModel.php';

class FinanceiroController
{
    public function __construct(private FinanceiroModel $model)
    {
    }
    public function coorte(): array
    {
        return $this->model->coorte();
    }
    public function resumo(int $coorteId): array
    {
        return $this->model->resumo($coorteId);
    }
    public function evolucaoMensal(int $coorteId, int $meses = 6): array
    {
        return $this->model->evolucaoMensal($coorteId, $meses);
    }
    public function participantes(int $coorteId): array
    {
        return $this->model->participantes($coorteId);
    }
    public function movimentacoes(int $coorteId, ?int $participanteId = null): array
    {
        return $this->model->movimentacoes($coorteId, $participanteId);
    }
    public function categorias(int $coorteId): array
    {
        return $this->model->categorias($coorteId);
    }
    public function despesasPorCategoria(int $coorteId): array
    {
        return $this->model->despesasPorCategoria($coorteId);
    }
    public function participantesAtivos(int $coorteId): array
    {
        return $this->model->participantesAtivos($coorteId);
    }
    public function parcelasAbertas(int $coorteId): array
    {
        return $this->model->parcelasAbertas($coorteId);
    }
    public function participanteDoUsuario(int $coorteId, int $usuarioId): ?array
    {
        return $this->model->participanteDoUsuario($coorteId, $usuarioId);
    }
    public function parcelasParticipante(int $participanteId): array
    {
        return $this->model->parcelasParticipante($participanteId);
    }
    public function adicionarParticipante(int $coorteId, string $nome, ?string $email, float $valor, int $usuarioId): void
    {
        if (mb_strlen(trim($nome)) < 2 || $valor <= 0)
            throw new InvalidArgumentException('Informe nome e contribuição mensal válidos.');
        $this->model->adicionarParticipante($coorteId, $nome, $email, $valor, $usuarioId);
    }
    public function lancar(int $coorteId, int $usuarioId, string $tipo, string $descricao, float $valor, string $data, string $meio, int $categoria, ?int $participante = null, ?int $parcela = null): void
    {
        if (trim($descricao) === '' || $valor <= 0)
            throw new InvalidArgumentException('Informe descrição e valor válido.');
        $this->model->lancar($coorteId, $usuarioId, $tipo, $descricao, $valor, $data, $meio, $categoria, $participante, $parcela);
    }
    public function atualizarCoorte(int $id, string $nome, float $meta, int $dia): void
    {
        if (trim($nome) === '' || $meta <= 0 || $dia < 1 || $dia > 28)
            throw new InvalidArgumentException('Confira o nome da turma, meta e dia de vencimento.');
        $this->model->atualizarCoorte($id, $nome, $meta, $dia);
    }
}
