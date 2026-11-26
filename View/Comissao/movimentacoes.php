<?php
$progresso = (float) ($resumoFinanceiro['progresso_percentual'] ?? 0);
$metaValor = (float) ($resumoFinanceiro['meta'] ?? 0);
$saldoValor = (float) ($resumoFinanceiro['saldo_atual'] ?? 0);
$receitasValor = (float) ($resumoFinanceiro['total_receitas'] ?? 0);
$despesasValor = (float) ($resumoFinanceiro['total_despesas'] ?? 0);
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
            <b>🎓</b>
            <span>
                <strong><?= e($coorteAtual['nome'] ?? '') ?></strong>
                <small>Código: <?= e($coorteAtual['codigo_convite'] ?? '') ?></small>
            </span>
        </div>

        <nav class="side-nav">
            <a href="?rota=comissao_dashboard"><i>◈</i> Visão Geral</a>
            <a href="?rota=comissao_movimentacoes" class="selected"><i>⇄</i> Movimentações</a>
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
            <span class="avatar"><?= e(iniciais((string) ($_SESSION['usuario_nome'] ?? 'CM'))) ?></span>
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
            <input type="text" readonly value="Livro-caixa da turma: <?= e($coorteAtual['nome'] ?? '') ?>">
        </div>
        <div class="top-right">
            <span class="fp-code-pill">Código: <?= e($coorteAtual['codigo_convite'] ?? '') ?></span>
            <div class="top-divider"></div>
            <a href="?rota=usuario_editar" class="fp-btn-secondary">Minha Conta</a>
        </div>
    </header>

    <main class="dash-main">
        <?php if (!empty($flashSucesso)): ?>
            <div class="notice success"><?= e($flashSucesso) ?></div>
        <?php endif; ?>
        <?php if (!empty($flashErro)): ?>
            <div class="notice error"><?= e($flashErro) ?></div>
        <?php endif; ?>

        <div class="ledger-heading">
            <div>
                <p class="eyebrow">LANÇAMENTOS E AUDITORIA DA TURMA</p>
                <h1>Movimentações Financeiras</h1>
                <p>Todos os lançamentos e estornos pertencem exclusivamente à coorte <strong><?= e($coorteAtual['nome'] ?? '') ?></strong>.</p>
            </div>
        </div>

        <section class="ledger-metrics">
            <article class="ledger-metric">
                <div class="metric-icon green">↑</div>
                <div>
                    <small>Receitas da Turma</small>
                    <b class="positive"><?= e(moeda($receitasValor)) ?></b>
                </div>
            </article>
            <article class="ledger-metric">
                <div class="metric-icon red">↓</div>
                <div>
                    <small>Despesas da Turma</small>
                    <b class="negative"><?= e(moeda($despesasValor)) ?></b>
                </div>
            </article>
            <article class="ledger-metric">
                <div class="metric-icon purple">◈</div>
                <div>
                    <small>Saldo Líquido</small>
                    <b><?= e(moeda($saldoValor)) ?></b>
                </div>
            </article>
        </section>

        <section class="panel" style="margin-bottom: 14px;">
            <div class="panel-head">
                <div>
                    <h2>Novo Lançamento na Turma</h2>
                    <p>Registre receitas (mensalidades, rifas, eventos) ou despesas (buffet, fotos, taxas)</p>
                </div>
            </div>

            <form method="POST" action="?rota=comissao_nova_movimentacao" class="fp-form-stack fp-form-grid-2" style="margin-top: 14px;">
                <?= csrf_field() ?>

                <label>
                    Tipo de movimentação *
                    <select name="tipo" required>
                        <option value="receita">Receita (Entrada)</option>
                        <option value="despesa">Despesa (Saída)</option>
                    </select>
                </label>

                <label>
                    Valor (R$) *
                    <input type="text" name="valor" required inputmode="decimal" placeholder="Ex.: 350.00 ou 1.250,00">
                </label>

                <label>
                    Categoria da turma
                    <select name="categoria_id">
                        <option value="">— Sem categoria específica —</option>
                        <?php foreach ($categoriasCoorte as $cat): ?>
                            <option value="<?= (int) $cat['id'] ?>">
                                <?= e($cat['nome']) ?> (<?= e(ucfirst((string) $cat['tipo'])) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label>
                    Meio de pagamento *
                    <select name="meio" required>
                        <option value="pix">PIX</option>
                        <option value="transferencia">Transferência</option>
                        <option value="dinheiro">Dinheiro</option>
                        <option value="cartao">Cartão</option>
                        <option value="outro">Outro</option>
                    </select>
                </label>

                <label>
                    Data do movimento *
                    <input type="date" name="data_movimento" required value="<?= e(date('Y-m-d')) ?>">
                </label>

                <label>
                    Participante vinculado (opcional)
                    <select name="participante_id">
                        <option value="">— Lançamento geral da turma —</option>
                        <?php foreach ($participantesCoorte as $part): ?>
                            <option value="<?= (int) $part['participante_id'] ?>">
                                <?= e($part['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label class="fp-form-span-2">
                    Descrição *
                    <input type="text" name="descricao" required maxlength="180" placeholder="Ex.: Mensalidade 03/2026 - Formando">
                </label>

                <div class="fp-form-span-2">
                    <button type="submit" class="primary-action">Registrar Movimentação</button>
                </div>
            </form>
        </section>

        <section class="panel ledger-panel">
            <div class="ledger-head">
                <div>
                    <h2>Extrato de Movimentações</h2>
                    <p><?= count($movimentacoes) ?> registro(s) encontrado(s) nesta turma</p>
                </div>
                <form method="GET" action="">
                    <input type="hidden" name="rota" value="comissao_movimentacoes">
                    <select name="tipo" onchange="this.form.submit()">
                        <option value="" <?= ($filtroTipo ?? null) === null ? 'selected' : '' ?>>Todos os tipos</option>
                        <option value="receita" <?= ($filtroTipo ?? null) === 'receita' ? 'selected' : '' ?>>Apenas receitas</option>
                        <option value="despesa" <?= ($filtroTipo ?? null) === 'despesa' ? 'selected' : '' ?>>Apenas despesas</option>
                    </select>
                </form>
            </div>

            <div class="table-scroll">
                <table class="ledger-table">
                    <thead>
                        <tr>
                            <th>Descrição</th>
                            <th>Categoria</th>
                            <th>Participante</th>
                            <th>Data / Meio</th>
                            <th>Valor / Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($movimentacoes)): ?>
                            <tr>
                                <td colspan="5" class="empty-filter">Nenhuma movimentação encontrada para este filtro.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($movimentacoes as $mov): ?>
                                <tr>
                                    <td>
                                        <span class="row-icon <?= $mov['tipo'] === 'receita' ? 'green' : 'red' ?>">
                                            <?= $mov['tipo'] === 'receita' ? '+' : '−' ?>
                                        </span>
                                        <div>
                                            <b><?= e($mov['descricao']) ?></b>
                                            <small>
                                                <?= e(ucfirst((string) $mov['tipo'])) ?>
                                                <?php if (!empty($mov['estorno_de'])): ?>
                                                    • Lançamento de estorno
                                                <?php endif; ?>
                                            </small>
                                        </div>
                                    </td>
                                    <td><span class="tag"><?= e($mov['categoria_nome']) ?></span></td>
                                    <td><?= e($mov['participante_nome'] ?? 'Geral') ?></td>
                                    <td><?= e($mov['data_movimento']) ?> • <?= e(strtoupper((string) $mov['meio'])) ?></td>
                                    <td class="<?= $mov['tipo'] === 'receita' ? 'money-in' : 'money-out' ?>">
                                        <?= $mov['tipo'] === 'receita' ? '+' : '-' ?> <?= e(moeda((float) $mov['valor'])) ?>
                                        <?php if (empty($mov['estorno_de']) && empty($mov['ja_estornado'])): ?>
                                            <form method="POST" action="?rota=comissao_estornar_movimentacao" style="display:inline-block;margin-left:8px;" onsubmit="return confirm('Registrar estorno auditado deste lançamento?');">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="movimentacao_id" value="<?= (int) $mov['id'] ?>">
                                                <button type="submit" class="fp-btn-danger-sm">Estornar</button>
                                            </form>
                                        <?php endif; ?>
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