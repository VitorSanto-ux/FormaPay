<?php
$progresso = (float) ($resumoFinanceiro['progresso_percentual'] ?? 0);
$metaValor = (float) ($resumoFinanceiro['meta'] ?? 0);
$saldoValor = (float) ($resumoFinanceiro['saldo_atual'] ?? 0);
$totalMinhas = array_reduce($minhasContribuicoes, static fn(float $acc, array $m): float => $acc + (float) $m['valor'], 0.0);
?>
<div class="participant-portal">
    <header class="portal-header">
        <div class="portal-header-inner">
            <a href="?rota=participante_dashboard" class="dash-brand">
                <img src="View/Shared/logo-formapay.png" alt="FormaPay" class="dash-logo-img">
                <span>
                    <b>Forma<span>Pay</span></b>
                    <small>ESPAÇO DO FORMANDO</small>
                </span>
            </a>

            <div class="portal-tools">
                <a href="?rota=usuario_editar" class="portal-config">Minha Conta</a>
                <div class="top-user">
                    <b><?= e($_SESSION['usuario_nome'] ?? '') ?></b>
                    <small>Participante</small>
                </div>
                <form method="POST" action="?rota=logout">
                    <?= csrf_field() ?>
                    <button type="submit" class="portal-logout" title="Sair">⎋</button>
                </form>
            </div>
        </div>
    </header>

    <main class="portal-main">
        <?php if (!empty($flashSucesso)): ?>
            <div class="notice success"><?= e($flashSucesso) ?></div>
        <?php endif; ?>

        <section class="portal-title portal-hero">
            <div>
                <p class="eyebrow">TURMA VINCULADA</p>
                <h1><?= e($coorteAtual['nome'] ?? 'Minha Turma') ?></h1>
                <p>Acompanhe a meta da formatura e o extrato transparente da sua turma.</p>
            </div>
            <div class="portal-cohort">
                <b>🎓</b>
                <span>
                    <strong style="color:#fff;"><?= e($coorteAtual['instituicao_curso'] ?? 'Formatura') ?></strong>
                    <small>Código: <?= e($coorteAtual['codigo_convite'] ?? '') ?></small>
                </span>
            </div>
        </section>

        <section class="portal-metrics">
            <article>
                <small>Meta da Turma</small>
                <b><?= e(moeda($metaValor)) ?></b>
                <span>Objetivo coletivo definido pela comissão</span>
            </article>
            <article>
                <small>Saldo Arrecadado da Turma</small>
                <b><?= e(moeda($saldoValor)) ?></b>
                <span class="on-track"><?= e((string) $progresso) ?>% da meta alcançada</span>
            </article>
            <article>
                <small>Minhas Contribuições Identificadas</small>
                <b><?= e(moeda($totalMinhas)) ?></b>
                <span><?= count($minhasContribuicoes) ?> lançamento(s) no seu nome</span>
            </article>
        </section>

        <section class="portal-plan-row">
            <div class="panel portal-plan">
                <div class="portal-section-head">
                    <div>
                        <h2>Progresso Coletivo da Formatura</h2>
                        <p>Vencimento padrão todo dia <?= (int) ($coorteAtual['vencimento_dia'] ?? 10) ?></p>
                    </div>
                    <span class="status-pill up-to-date">Turma Ativa</span>
                </div>

                <div class="plan-progress-head">
                    <span>Arrecadado líquido</span>
                    <b><?= e((string) $progresso) ?>%</b>
                </div>
                <div class="plan-progress"><i style="width: <?= min(100, max(0, $progresso)) ?>%;"></i></div>

                <div class="next-due">
                    <div class="due-date">
                        <b>DIA</b>
                        <strong><?= (int) ($coorteAtual['vencimento_dia'] ?? 10) ?></strong>
                    </div>
                    <div>
                        <b>Falta para atingir a meta</b>
                        <small>Valor restante: <?= e(moeda((float) ($resumoFinanceiro['falta_meta'] ?? 0))) ?></small>
                    </div>
                    <span class="due-status">Em andamento</span>
                </div>
            </div>

            <aside class="collective-goal">
                <small>META COLETIVA</small>
                <h3>Percentual atingido</h3>
                <strong><?= e((string) $progresso) ?>%</strong>
                <div class="collective-progress"><i style="width: <?= min(100, max(0, $progresso)) ?>%;"></i></div>
                <p class="collective-balance"><?= e(moeda($saldoValor)) ?></p>
                <p>de <?= e(moeda($metaValor)) ?> previstos</p>
            </aside>
        </section>

        <section class="panel portal-history">
            <div class="portal-section-head">
                <div>
                    <h2>Extrato Financeiro da Turma</h2>
                    <p>Movimentações públicas da sua coorte</p>
                </div>
            </div>
            <div class="table-scroll">
                <table class="ledger-table">
                    <thead>
                        <tr>
                            <th>Descrição</th>
                            <th>Categoria</th>
                            <th>Data</th>
                            <th>Meio</th>
                            <th style="text-align:right;">Valor</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($movimentacoesTurma)): ?>
                            <tr><td colspan="5">Nenhuma movimentação registrada ainda.</td></tr>
                        <?php else: ?>
                            <?php foreach ($movimentacoesTurma as $mov): ?>
                                <tr>
                                    <td>
                                        <span class="row-icon <?= $mov['tipo'] === 'receita' ? 'green' : 'red' ?>">
                                            <?= $mov['tipo'] === 'receita' ? '+' : '−' ?>
                                        </span>
                                        <div>
                                            <b><?= e($mov['descricao']) ?></b>
                                            <small><?= e(ucfirst((string) $mov['tipo'])) ?></small>
                                        </div>
                                    </td>
                                    <td><span class="tag"><?= e($mov['categoria_nome']) ?></span></td>
                                    <td><?= e($mov['data_movimento']) ?></td>
                                    <td><?= e(strtoupper((string) $mov['meio'])) ?></td>
                                    <td class="<?= $mov['tipo'] === 'receita' ? 'money-in' : 'money-out' ?>">
                                        <?= $mov['tipo'] === 'receita' ? '+' : '-' ?> <?= e(moeda((float) $mov['valor'])) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</div>