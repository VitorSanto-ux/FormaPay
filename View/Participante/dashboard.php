<?php
$progresso = (float) ($resumoFinanceiro['progresso_percentual'] ?? 0);
$metaValor = (float) ($resumoFinanceiro['meta'] ?? 0);
$saldoValor = (float) ($resumoFinanceiro['saldo_atual'] ?? 0);
$totalMinhas = (float) ($totalContribuicoesParticipante ?? 0);
?>
<div class="participant-portal" data-latest-movement="<?= e($ultimaMovimentacaoId) ?>">
    <header class="portal-header">
        <div class="portal-header-inner">
            <a href="?rota=participante_dashboard" class="dash-brand">
                <img src="View/Shared/logo-formapay.png" alt="FormaPay" class="dash-logo-img">
                <span>
                    <b>Forma<span>Pay</span></b>
                    <small>ESPAÇO DO FORMANDO</small>
                </span>
            </a>

            <label class="portal-search">
                <span aria-hidden="true">⌕</span>
                <input type="search" data-page-search placeholder="Pesquisar no extrato..." aria-label="Pesquisar movimentações no extrato da turma">
            </label>

            <div class="portal-tools">
                <?php require __DIR__ . '/../Shared/notifications.php'; ?>
                <a href="?rota=usuario_editar" class="portal-config">Minha Conta</a>
                <div class="top-user">
                    <?php if (!empty($_SESSION['foto_perfil'])): ?>
                        <img class="portal-profile-photo" src="<?= e($_SESSION['foto_perfil']) ?>" alt="Foto de perfil">
                    <?php else: ?>
                        <span class="portal-profile-initials"><?= e(iniciais((string) ($_SESSION['usuario_nome'] ?? 'PT'))) ?></span>
                    <?php endif; ?>
                    <span class="portal-profile-name">
                    <b><?= e($_SESSION['usuario_nome'] ?? '') ?></b>
                    <small>Participante</small>
                    </span>
                </div>
                <form method="POST" action="?rota=logout">
                    <?= csrf_field() ?>
                    <button type="submit" class="portal-logout" title="Sair">⎋</button>
                </form>
            </div>
        </div>
    </header>

    <main class="portal-main">
        <?php require __DIR__ . '/../Shared/goal-celebration.php'; ?>
        <?php if (!empty($flashSucesso)): ?>
            <div class="notice success"><?= e($flashSucesso) ?></div>
        <?php endif; ?>
        <?php if (!empty($flashErro)): ?>
            <div class="notice error"><?= e($flashErro) ?></div>
        <?php endif; ?>

        <section class="portal-title portal-hero">
            <div>
                <p class="eyebrow">TURMA VINCULADA</p>
                <h1><?= e($coorteAtual['nome'] ?? 'Minha Turma') ?></h1>
                <p>Acompanhe a meta da formatura e o extrato transparente da sua turma.</p>
            </div>
            <div class="portal-cohort">
                <?php if (!empty($coorteAtual['foto_turma'])): ?><img class="portal-cohort-photo" src="<?= e($coorteAtual['foto_turma']) ?>" alt="Foto da turma"><?php else: ?><b>🎓</b><?php endif; ?>
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

        <section class="panel portal-history portal-payment-card">
            <div class="portal-payment-heading">
                <span class="portal-payment-icon" aria-hidden="true">R$</span>
                <div><p class="eyebrow">CONTRIBUIÇÃO</p><h2>Registrar pagamento</h2><p>Envie os dados para conferência da comissão. O valor entra no saldo da turma após a aprovação.</p></div>
            </div>
            <?php if (!empty($dadosRecebimento['pix_chave'])): ?><p><strong>Pix:</strong> <?= e($dadosRecebimento['pix_chave']) ?></p><?php endif; ?>
            <?php if (!empty($dadosRecebimento['banco_nome'])): ?><p><strong>Transferência:</strong> <?= e($dadosRecebimento['banco_nome']) ?> · Agência <?= e($dadosRecebimento['banco_agencia'] ?? '') ?> · Conta <?= e($dadosRecebimento['banco_conta'] ?? '') ?></p><?php endif; ?>
            <?php if (!empty($dadosRecebimento['cartao_link'])): ?><p><a href="<?= e($dadosRecebimento['cartao_link']) ?>" target="_blank" rel="noopener noreferrer">Pagar com cartão pelo link seguro</a></p><?php endif; ?>
            <form id="participant-payment-form" method="POST" enctype="multipart/form-data" action="?rota=participante_solicitar_pagamento" class="fp-form-stack fp-form-grid-2 portal-payment-form">
                <?= csrf_field() ?>
                <label>Tipo de transação *<select name="tipo" id="payment-type" required><option value="receita">Receita / contribuição recebida</option><option value="despesa">Despesa paga em nome da turma</option></select></label>
                <label>Categoria *<select name="categoria_id" required><option value="">Selecione uma categoria</option><?php foreach (($categoriasCoorte ?? []) as $categoria): ?><option value="<?= (int)$categoria['id'] ?>" data-tipo="<?= e($categoria['tipo']) ?>"><?= e($categoria['nome']) ?></option><?php endforeach; ?></select></label>
                <label>Valor (R$) *<input name="valor" required inputmode="decimal" placeholder="Ex.: 150,00"></label>
                <label>Forma de pagamento *<select name="meio" required><option value="pix">Pix</option><option value="boleto">Boleto</option><option value="transferencia">Transferência</option><option value="cartao">Cartão</option><option value="dinheiro">Dinheiro</option><option value="outro">Outro</option></select></label>
                <label>Data do pagamento *<input type="date" name="data_pagamento" required value="<?= e(date('Y-m-d')) ?>"></label>
                <label>Descrição<input name="descricao" maxlength="180" value="Contribuição"></label>
                <label id="payment-reference-field" hidden>Referência<input name="referencia" maxlength="100" placeholder="Código da transação"></label>
                <label id="payment-card-field" hidden>Últimos 4 dígitos do cartão<input name="cartao_final4" inputmode="numeric" pattern="[0-9]{4}" maxlength="4" placeholder="1234"></label>
                <label id="payment-proof-field" hidden>Comprovante <span class="payment-proof-hint">Obrigatório para Pix e boleto · PDF, JPG ou PNG · até 4 MB</span><input id="payment-proof" type="file" name="comprovante" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png"></label>
                <div class="portal-payment-submit"><button class="primary-action">Enviar pagamento para aprovação</button><small>Você poderá acompanhar o status da solicitação abaixo.</small></div>
            </form>
            <div class="portal-payment-requests"><h3>Minhas solicitações</h3>
                <?php if (empty($minhasSolicitacoesPagamento)): ?><p class="payment-empty">Suas solicitações de pagamento aparecerão aqui.</p><?php endif; ?>
                <?php foreach ($minhasSolicitacoesPagamento as $sol): ?>
                    <div class="payment-request-row"><span><strong><?= e($sol['descricao']) ?></strong><small><?= e(strtoupper($sol['meio'])) ?> · <?= e($sol['data_pagamento']) ?></small></span><b><?= e(moeda((float)$sol['valor'])) ?></b><span class="payment-status <?= e($sol['status']) ?>"><?= e(ucfirst($sol['status'])) ?></span><?php if($sol['comprovante']): ?><a href="<?= e($sol['comprovante']) ?>" target="_blank" rel="noopener">Comprovante</a><?php endif; ?></div>
                <?php endforeach; ?>
            </div>
        </section>
    </main>
</div>
<script>
(() => {
    const form = document.getElementById('participant-payment-form');
    if (!form) return;
    const method = form.elements.meio;
    const type = document.getElementById('payment-type');
    const proof = document.getElementById('payment-proof');
    const proofField = document.getElementById('payment-proof-field');
    const cardField = document.getElementById('payment-card-field');
    const referenceField = document.getElementById('payment-reference-field');
    const category = form.elements.categoria_id;
    const updateFields = () => {
        const proofNeeded = ['pix','boleto'].includes(method.value);
        proofField.hidden = !proofNeeded; proof.required = proofNeeded;
        cardField.hidden = method.value !== 'cartao';
        form.elements.cartao_final4.required = method.value === 'cartao';
        referenceField.hidden = !['transferencia','outro'].includes(method.value);
        [...category.options].forEach(option => {
            if (!option.value) return;
            const visible = !option.dataset.tipo || option.dataset.tipo === type.value || option.dataset.tipo === 'ambos';
            option.hidden = !visible;
            if (!visible && option.selected) category.value = '';
        });
    };
    method.addEventListener('change', updateFields);
    type.addEventListener('change', updateFields);
    updateFields();
})();
(() => {
    const portal = document.querySelector('.participant-portal[data-latest-movement]');
    if (!portal) return;
    const latestVersion = portal.dataset.latestMovement || '';
    window.setInterval(async () => {
        if (document.hidden) return;
        try {
            const response = await fetch('?rota=participante_verificar_atualizacao', { cache: 'no-store', headers: { 'Accept': 'application/json' } });
            if (!response.ok) return;
            const payload = await response.json();
            if (String(payload.versao_movimentacoes) !== latestVersion) window.location.reload();
        } catch (_) { /* A próxima verificação tentará novamente. */ }
    }, 15000);
})();
</script>
