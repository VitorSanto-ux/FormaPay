<?php
$progresso = (float) ($resumoFinanceiro['progresso_percentual'] ?? 0);
$metaValor = (float) ($resumoFinanceiro['meta'] ?? 0);
$saldoValor = (float) ($resumoFinanceiro['saldo_atual'] ?? 0);
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
            <a href="?rota=comissao_movimentacoes"><i>⇄</i> Movimentações</a>
            <a href="?rota=comissao_participantes"><i>☺</i> Participantes <em><?= (int) ($resumoFinanceiro['total_participantes'] ?? 0) ?></em></a>
            <a href="?rota=comissao_relatorios"><i>▤</i> Relatórios</a>
            <a href="?rota=comissao_configuracoes" class="selected"><i>⚙</i> Configurações</a>
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
            <input type="text" readonly value="Configurações da turma — <?= e($coorteAtual['nome'] ?? '') ?>">
        </div>
        <div class="top-right">
            <span class="fp-code-pill">Código: <?= e($coorteAtual['codigo_convite'] ?? '') ?></span>
            <div class="top-divider"></div>
            <a href="?rota=usuario_editar" class="fp-btn-secondary">Minha Conta</a>
        </div>
    </header>

    <main class="dash-main settings-main">
        <?php if (!empty($flashSucesso)): ?>
            <div class="notice success"><?= e($flashSucesso) ?></div>
        <?php endif; ?>
        <?php if (!empty($flashErro)): ?>
            <div class="notice error"><?= e($flashErro) ?></div>
        <?php endif; ?>

        <h1>Configurações da Turma</h1>
        <p class="settings-intro">Ajuste o nome da turma, a meta financeira, o vencimento e o código de convite dos participantes.</p>

        <div class="settings-grid">
            <section class="panel settings-card">
                <h2>Identidade e Meta Financeira</h2>
                <p>Alterações aplicadas somente à turma <strong><?= e($coorteAtual['nome'] ?? '') ?></strong>.</p>

                <form method="POST" action="?rota=comissao_salvar_config" class="fp-form-stack">
                    <?= csrf_field() ?>

                    <label>
                        Nome da turma dos formandos *
                        <input type="text" name="nome" required minlength="3" value="<?= e($coorteAtual['nome'] ?? '') ?>">
                    </label>

                    <label>
                        Meta financeira (R$) *
                        <input type="text" name="meta" required value="<?= e(number_format($metaValor, 2, '.', '')) ?>">
                    </label>

                    <label>
                        Instituição / Curso
                        <input type="text" name="instituicao_curso" value="<?= e($coorteAtual['instituicao_curso'] ?? '') ?>">
                    </label>

                    <label>
                        Ano de formatura
                        <input type="number" name="ano_formatura" min="2020" max="2050" value="<?= e((string) ($coorteAtual['ano_formatura'] ?? '')) ?>">
                    </label>

                    <label>
                        Dia de vencimento padrão (1 a 28)
                        <input type="number" name="vencimento_dia" min="1" max="28" required value="<?= (int) ($coorteAtual['vencimento_dia'] ?? 10) ?>">
                    </label>

                    <label style="display:flex;align-items:center;gap:8px;flex-direction:row;">
                        <input type="checkbox" name="ativa" value="1" <?= !empty($coorteAtual['ativa']) ? 'checked' : '' ?>>
                        <span>Turma ativa para acesso dos participantes</span>
                    </label>

                    <button type="submit" class="primary-action">Salvar Configurações da Turma</button>
                </form>
            </section>

            <section class="panel settings-card">
                <h2>Código Único de Convite</h2>
                <p>Compartilhe este código com os formandos para que entrem na turma correta.</p>

                <div style="padding:14px;border-radius:10px;background:#fbf9ff;border:1px solid #e5d8fb;margin-bottom:14px;">
                    <small style="display:block;color:#8d8298;margin-bottom:6px;">CÓDIGO ATUAL</small>
                    <strong style="font-size:20px;letter-spacing:1.2px;color:#6826c8;"><?= e($coorteAtual['codigo_convite'] ?? '') ?></strong>
                </div>

                <form method="POST" action="?rota=comissao_regenerar_codigo" onsubmit="return confirm('Gerar um novo código invalidará o código anterior para novos ingressos. Continuar?');">
                    <?= csrf_field() ?>
                    <button type="submit" class="primary-action">Regenerar Código da Turma</button>
                </form>

                <div class="years-list">
                    <div><b>ID da Turma:</b> #<?= (int) ($coorteAtual['id'] ?? 0) ?></div>
                    <div><b>Participantes vinculados:</b> <?= (int) ($resumoFinanceiro['total_participantes'] ?? 0) ?></div>
                    <div><b>Total de lançamentos:</b> <?= (int) ($resumoFinanceiro['total_lancamentos'] ?? 0) ?></div>
                </div>
            </section>
        </div>
    </main>
</div>