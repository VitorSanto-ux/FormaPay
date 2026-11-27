<?php
$progresso = (float) ($resumoFinanceiro['progresso_percentual'] ?? 0);
$metaValor = (float) ($resumoFinanceiro['meta'] ?? 0);
$saldoValor = (float) ($resumoFinanceiro['saldo_atual'] ?? 0);
$receitasValor = (float) ($resumoFinanceiro['total_receitas'] ?? 0);
$despesasValor = (float) ($resumoFinanceiro['total_despesas'] ?? 0);
$progressoGrafico = min(100, max(0, $progresso));
$resumoMensalPorMes = [];
foreach (($resumoMensal ?? []) as $linhaMensal) {
    $resumoMensalPorMes[$linhaMensal['mes']] = $linhaMensal;
}
$nomesMeses = [1 => 'jan', 'fev', 'mar', 'abr', 'mai', 'jun', 'jul', 'ago', 'set', 'out', 'nov', 'dez'];
$mesAtual = new DateTimeImmutable('first day of this month');
$mesesGrafico = [];
$maiorMovimentoMensal = 0.0;
for ($deslocamento = 5; $deslocamento >= 0; $deslocamento--) {
    $periodo = $mesAtual->modify('-' . $deslocamento . ' months');
    $chaveMes = $periodo->format('Y-m');
    $linhaMensal = $resumoMensalPorMes[$chaveMes] ?? [];
    $receitasMes = (float) ($linhaMensal['total_receitas'] ?? 0);
    $despesasMes = (float) ($linhaMensal['total_despesas'] ?? 0);
    $mesesGrafico[] = [
        'nome' => $nomesMeses[(int) $periodo->format('n')],
        'receitas' => $receitasMes,
        'despesas' => $despesasMes,
    ];
    $maiorMovimentoMensal = max($maiorMovimentoMensal, $receitasMes, $despesasMes);
}
$escalaGrafico = max(1, $maiorMovimentoMensal);
?>
<div class="dashboard">
    <aside class="sidebar">
        <a href="?rota=comissao_dashboard" class="dash-brand">
            <img src="View/Shared/logo-formapay.png" alt="FormaPay" class="dash-logo-img">
            <span>
                <b>Forma<span>Pay</span></b>
                <small>COMISSÃO DE FORMATURA</small>
            </span>
        </a>

        <p class="side-caption">TURMA ATIVA</p>
        <div class="cohort">
            <?php if (!empty($coorteAtual['foto_turma'])): ?><img class="cohort-photo" src="<?= e($coorteAtual['foto_turma']) ?>" alt="Foto da turma"><?php else: ?><b>🎓</b><?php endif; ?>
            <span>
                <strong><?= e($coorteAtual['nome'] ?? '') ?></strong>
                <small>Código: <?= e($coorteAtual['codigo_convite'] ?? '') ?></small>
            </span>
        </div>

        <nav class="side-nav">
            <a href="?rota=comissao_dashboard" class="selected"><i>◈</i> Visão Geral</a>
            <a href="?rota=comissao_movimentacoes"><i>⇄</i> Movimentações</a>
            <a href="?rota=comissao_participantes"><i>☺</i> Participantes <em><?= (int) ($resumoFinanceiro['total_participantes'] ?? 0) ?></em></a>
            <a href="?rota=comissao_relatorios"><i>▤</i> Relatórios</a>
            <a href="?rota=comissao_configuracoes"><i>⚙</i> Configurações</a>
            <a href="?rota=comissao_onboarding"><i>+</i> Nova Turma</a>
        </nav>

        <div class="goal-mini">
            <b>Meta da Turma (<?= e((string) $progresso) ?>%)</b>
            <p><?= e(moeda($saldoValor)) ?> de <?= e(moeda($metaValor)) ?></p>
            <div class="mini-track"><i style="width: <?= min(100, max(0, $progresso)) ?>%;"></i></div>
        </div>

        <div class="profile">
            <span class="avatar">
                <?php if (!empty($_SESSION['foto_perfil'])): ?>
                    <img src="<?= e($_SESSION['foto_perfil']) ?>" alt="Foto de perfil">
                <?php else: ?>
                    <?= e(iniciais((string) ($_SESSION['usuario_nome'] ?? 'CM'))) ?>
                <?php endif; ?>
            </span>
            <span>
                <b><?= e($_SESSION['usuario_nome'] ?? '') ?></b>
                <small>Comissão</small>
            </span>
            <form method="POST" action="?rota=logout">
                <?= csrf_field() ?>
                <button type="submit" title="Sair">⎋</button>
            </form>
        </div>
    </aside>

    <header class="topbar">
        <div class="search">
            <span>⌕</span>
            <input type="search" data-page-search placeholder="Pesquisar movimentações..." aria-label="Pesquisar movimentações">
        </div>
        <div class="top-right">
            <?php require __DIR__ . '/../Shared/notifications.php'; ?>
            <button type="button" class="fp-btn-secondary fp-code-pill copy-code-button" data-copy-code="<?= e($coorteAtual['codigo_convite'] ?? '') ?>" aria-label="Copiar código de convite">
                <span aria-hidden="true"></span> Copiar código <strong><?= e($coorteAtual['codigo_convite'] ?? '') ?></strong>
            </button>
            <div class="top-divider"></div>
        </div>
    </header>

    <main class="dash-main">
        <?php require __DIR__ . '/../Shared/goal-celebration.php'; ?>
        <?php if (!empty($flashSucesso)): ?>
            <div class="notice success"><?= e($flashSucesso) ?></div>
        <?php endif; ?>
        <?php if (!empty($flashErro)): ?>
            <div class="notice error"><?= e($flashErro) ?></div>
        <?php endif; ?>

        <div class="dash-title">
            <div>
                <p class="eyebrow">PAINEL FINANCEIRO ISOLADO POR TURMA</p>
                <div class="dash-title-name">
                    <?php if (!empty($coorteAtual['foto_turma'])): ?>
                        <img class="dash-title-cohort-photo" src="<?= e($coorteAtual['foto_turma']) ?>" alt="Foto da turma" width="44" height="44">
                    <?php else: ?>
                        <span class="dash-title-cohort-placeholder" aria-hidden="true">🎓</span>
                    <?php endif; ?>
                    <h1><?= e($coorteAtual['nome'] ?? '') ?> <span>• <?= e((string) ($coorteAtual['ano_formatura'] ?? 'Formatura')) ?></span></h1>
                </div>
                <p>Compartilhe o código <strong><?= e($coorteAtual['codigo_convite'] ?? '') ?></strong> apenas com os participantes desta turma.</p>
            </div>
            <div class="dash-actions">
                <?php if (!empty($coortesComissao) && count($coortesComissao) > 1): ?>
                    <form method="POST" action="?rota=comissao_trocar_turma">
                        <?= csrf_field() ?>
                        <select name="coorte_id" class="select-year" onchange="this.form.submit()">
                            <?php foreach ($coortesComissao as $c): ?>
                                <option value="<?= (int) $c['id'] ?>" <?= (int) $c['id'] === (int) $coorteAtual['id'] ? 'selected' : '' ?>>
                                    <?= e($c['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                <?php endif; ?>
                <a href="?rota=comissao_movimentacoes" class="primary-action" style="display:inline-flex;align-items:center;text-decoration:none;">+ Novo Lançamento</a>
            </div>
        </div>

        <section class="metrics">
            <article class="metric">
                <div class="metric-icon purple">★</div>
                <small>Meta Financeira</small>
                <strong><?= e(moeda($metaValor)) ?></strong>
                <div class="progress"><i style="width: <?= min(100, max(0, $progresso)) ?>%;"></i></div>
                <p class="metric-caption"><b><?= e((string) $progresso) ?>%</b> atingido</p>
            </article>

            <article class="metric">
                <div class="metric-icon green">↑</div>
                <small>Total de Receitas</small>
                <strong><?= e(moeda($receitasValor)) ?></strong>
                <p class="positive">Entradas acumuladas</p>
            </article>

            <article class="metric">
                <div class="metric-icon red">↓</div>
                <small>Total de Despesas</small>
                <strong><?= e(moeda($despesasValor)) ?></strong>
                <p class="negative">Saídas registradas</p>
            </article>

            <article class="metric">
                <div class="metric-icon amber">◈</div>
                <small>Saldo Líquido Atual</small>
                <strong><?= e(moeda($saldoValor)) ?></strong>
                <p>Falta para meta: <span><?= e(moeda((float) ($resumoFinanceiro['falta_meta'] ?? 0))) ?></span></p>
            </article>
        </section>

        <section class="charts-row" aria-label="Resumo gráfico das finanças da turma">
            <article class="panel evolution">
                <div class="panel-head">
                    <div>
                        <h2>Receitas e despesas</h2>
                        <p>Movimentações registradas nos últimos seis meses</p>
                    </div>
                </div>
                <div class="chart-legend" aria-hidden="true">
                    <span><i></i> Receitas</span>
                    <span><i></i> Despesas</span>
                </div>
                <div class="bar-chart" role="img" aria-label="Gráfico mensal comparando receitas e despesas">
                    <div class="y-labels" aria-hidden="true">
                        <span><?= e(moeda($maiorMovimentoMensal)) ?></span>
                        <span><?= e(moeda($maiorMovimentoMensal / 2)) ?></span>
                        <span>R$ 0</span>
                    </div>
                    <div class="plot">
                        <i class="gridline g1"></i>
                        <i class="gridline g2"></i>
                        <i class="gridline g3"></i>
                        <i class="gridline g4"></i>
                        <?php foreach ($mesesGrafico as $mes): ?>
                            <?php
                            $alturaReceitas = ($mes['receitas'] / $escalaGrafico) * 100;
                            $alturaDespesas = ($mes['despesas'] / $escalaGrafico) * 100;
                            ?>
                            <div class="month" aria-hidden="true">
                                <div class="bars">
                                    <i title="Receitas: <?= e(moeda($mes['receitas'])) ?>" style="height: <?= e((string) $alturaReceitas) ?>%;"></i>
                                    <i title="Despesas: <?= e(moeda($mes['despesas'])) ?>" style="height: <?= e((string) $alturaDespesas) ?>%;"></i>
                                </div>
                                <small><?= e($mes['nome']) ?></small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </article>

            <article class="panel goal-panel">
                <div class="panel-head">
                    <div>
                        <h2>Progresso da meta</h2>
                        <p>Saldo líquido em relação ao objetivo da turma</p>
                    </div>
                </div>
                <div class="donut" role="img" aria-label="<?= e((string) $progressoGrafico) ?>% da meta atingida" style="background: conic-gradient(#722bdc 0 <?= e((string) $progressoGrafico) ?>%, #eee8f3 <?= e((string) $progressoGrafico) ?>% 100%);">
                    <div>
                        <b><?= e((string) $progresso) ?>%</b>
                        <small>da meta</small>
                    </div>
                </div>
                <div class="goal-values">
                    <div>
                        <small>Saldo atual</small>
                        <b><?= e(moeda($saldoValor)) ?></b>
                    </div>
                    <i></i>
                    <div>
                        <small>Meta</small>
                        <b><?= e(moeda($metaValor)) ?></b>
                    </div>
                </div>
                <div class="goal-breakdown" aria-label="Receitas e despesas da turma">
                    <div class="goal-breakdown-item revenue"><i aria-hidden="true"></i><span>Receitas</span><b><?= e(moeda($receitasValor)) ?></b></div>
                    <div class="goal-breakdown-item expense"><i aria-hidden="true"></i><span>Despesas</span><b><?= e(moeda($despesasValor)) ?></b></div>
                </div>
                <div class="projection">
                    <b>Falta para alcançar a meta: <?= e(moeda((float) ($resumoFinanceiro['falta_meta'] ?? 0))) ?></b>
                    <small>O cálculo considera o saldo líquido acumulado da turma.</small>
                </div>
            </article>
        </section>

        <section class="panel recent">
            <div class="panel-head">
                <div>
                    <h2>Últimas Movimentações da Turma</h2>
                    <p>Lançamentos registrados exclusivamente nesta coorte</p>
                </div>
                <a href="?rota=comissao_movimentacoes">Ver todas →</a>
            </div>
            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>Descrição</th>
                            <th>Categoria</th>
                            <th>Participante</th>
                            <th>Data / Meio</th>
                            <th style="text-align:right;">Valor</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($ultimasMovimentacoes)): ?>
                            <tr><td colspan="5">Nenhuma movimentação registrada ainda nesta turma.</td></tr>
                        <?php else: ?>
                            <?php foreach ($ultimasMovimentacoes as $mov): ?>
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
                                    <td><?= e($mov['participante_nome'] ?? 'Geral da turma') ?></td>
                                    <td><?= e($mov['data_movimento']) ?> • <?= e(strtoupper((string) $mov['meio'])) ?></td>
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
