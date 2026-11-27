<?php
$progresso = (float) ($resumoFinanceiro['progresso_percentual'] ?? 0);
$metaValor = (float) ($resumoFinanceiro['meta'] ?? 0);
$saldoValor = (float) ($resumoFinanceiro['saldo_atual'] ?? 0);
$receitasValor = (float) ($resumoFinanceiro['total_receitas'] ?? 0);
$despesasValor = (float) ($resumoFinanceiro['total_despesas'] ?? 0);
$maiorCategoria = 0.0;
foreach ($resumoCategorias as $rc) {
    $maiorCategoria = max($maiorCategoria, (float) ($rc['total'] ?? 0));
}
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
            <a href="?rota=comissao_dashboard"><i>◈</i> Visão Geral</a>
            <a href="?rota=comissao_movimentacoes"><i>⇄</i> Movimentações</a>
            <a href="?rota=comissao_participantes"><i>☺</i> Participantes <em><?= (int) ($resumoFinanceiro['total_participantes'] ?? 0) ?></em></a>
            <a href="?rota=comissao_relatorios" class="selected"><i>▤</i> Relatórios</a>
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
            <input type="search" data-page-search placeholder="Pesquisar no relatório..." aria-label="Pesquisar no relatório">
        </div>
        <div class="top-right">
            <?php require __DIR__ . '/../Shared/notifications.php'; ?>
            <button type="button" class="fp-btn-secondary fp-code-pill copy-code-button" data-copy-code="<?= e($coorteAtual['codigo_convite'] ?? '') ?>" aria-label="Copiar código de convite">
                <span aria-hidden="true"></span> Copiar código <strong><?= e($coorteAtual['codigo_convite'] ?? '') ?></strong>
            </button>
            <div class="top-divider"></div>
            <button type="button" class="fp-btn-secondary" onclick="window.print()">Imprimir / Salvar PDF</button>
            <div class="top-divider"></div>
        </div>
    </header>

    <main class="dash-main">
        <?php require __DIR__ . '/../Shared/goal-celebration.php'; ?>
        <div class="report-heading">
            <div>
                <p class="eyebrow">PRESTAÇÃO DE CONTAS POR TURMA</p>
                <h1>Relatórios da Turma</h1>
                <p>Consolidado exclusivo de <strong><?= e($coorteAtual['nome'] ?? '') ?></strong> sem mistura de outras formaturas.</p>
            </div>
        </div>

        <section class="period-banner">
            <div>
                <small>TURMA SELECIONADA • CÓDIGO <?= e($coorteAtual['codigo_convite'] ?? '') ?></small>
                <h2><?= e($coorteAtual['nome'] ?? '') ?></h2>
                <p>Meta estipulada: <?= e(moeda($metaValor)) ?> • Progresso atual: <?= e((string) $progresso) ?>%</p>
            </div>
            <div class="period-balance">
                <small>SALDO LÍQUIDO</small>
                <b><?= e(moeda($saldoValor)) ?></b>
                <span>Falta para meta: <?= e(moeda((float) ($resumoFinanceiro['falta_meta'] ?? 0))) ?></span>
            </div>
        </section>

        <section class="report-summaries">
            <div class="panel financial-summary">
                <h2>Resumo Financeiro</h2>
                <div class="summary-line">
                    <span><i class="dot purple-dot"></i> Total de receitas</span>
                    <b><?= e(moeda($receitasValor)) ?></b>
                </div>
                <div class="summary-line">
                    <span><i class="dot gray-dot"></i> Total de despesas</span>
                    <b><?= e(moeda($despesasValor)) ?></b>
                </div>
                <div class="summary-line total-line">
                    <span>Saldo disponível</span>
                    <b><?= e(moeda($saldoValor)) ?></b>
                </div>
                <div class="reconciled" style="margin-top: 14px;">
                    <b>✓ Isolamento por coorte ativo (ID #<?= (int) ($coorteAtual['id'] ?? 0) ?>)</b>
                    <small>Apenas movimentações desta turma compõem os totais acima.</small>
                </div>
            </div>

            <div class="panel category-summary">
                <h2>Totais por Categoria</h2>
                <?php if (empty($resumoCategorias)): ?>
                    <p style="color:#8d8298;font-size:12px;">Sem lançamentos categorizados ainda.</p>
                <?php else: ?>
                    <?php foreach ($resumoCategorias as $rc): ?>
                        <?php
                        $valCat = (float) ($rc['total'] ?? 0);
                        $pctCat = $maiorCategoria > 0 ? round(($valCat / $maiorCategoria) * 100, 1) : 0;
                        ?>
                        <div class="category-row">
                            <div>
                                <span><?= e($rc['categoria']) ?> (<?= e(ucfirst((string) $rc['tipo'])) ?>)</span>
                                <b><?= e(moeda($valCat)) ?></b>
                            </div>
                            <div class="category-track"><i style="width: <?= min(100, max(0, $pctCat)) ?>%;"></i></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>

        <section class="panel report-table-panel">
            <div class="report-table-head">
                <div>
                    <h2>Extrato Completo da Turma</h2>
                    <p><?= count($movimentacoes) ?> lançamento(s) registrados</p>
                </div>
            </div>
            <div class="table-scroll">
                <table class="ledger-table">
                    <thead>
                        <tr>
                            <th>Descrição</th>
                            <th>Categoria</th>
                            <th>Participante</th>
                            <th>Data</th>
                            <th>Valor</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($movimentacoes)): ?>
                            <tr><td colspan="5" class="empty-filter">Nenhum lançamento registrado nesta turma.</td></tr>
                        <?php else: ?>
                            <?php foreach ($movimentacoes as $mov): ?>
                                <tr>
                                    <td>
                                        <span class="row-icon <?= $mov['tipo'] === 'receita' ? 'green' : 'red' ?>">
                                            <?= $mov['tipo'] === 'receita' ? '+' : '−' ?>
                                        </span>
                                        <div>
                                            <b><?= e($mov['descricao']) ?></b>
                                            <small><?= e(ucfirst((string) $mov['tipo'])) ?> • <?= e(strtoupper((string) $mov['meio'])) ?></small>
                                        </div>
                                    </td>
                                    <td><span class="tag"><?= e($mov['categoria_nome']) ?></span></td>
                                    <td><?= e($mov['participante_nome'] ?? 'Geral') ?></td>
                                    <td><?= e($mov['data_movimento']) ?></td>
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
